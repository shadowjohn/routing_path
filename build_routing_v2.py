#!/usr/bin/env python3
"""
OSM PBF → taiwan_routing_v2.sqlite
建立支援汽車/機車/步行的 VirtualNetwork 路由資料庫

用法：
  python3 build_routing_v2.py             # 全流程
  python3 build_routing_v2.py --roads     # 只建 roads 表（跳過 VirtualNetwork，快速驗證）
  python3 build_routing_v2.py --routing   # 只建 VirtualNetwork（roads 表已存在）
  python3 build_routing_v2.py --demo-routing # 只建展示用雙向圖（既有 DB）

預設在程式所在目錄產出 taiwan_routing_v2.sqlite。
"""

import os, sys, sqlite3, math, time, argparse

try:
    import osmium
except ImportError:
    sys.exit("需要 pyosmium: pip3 install osmium")

PBF_PATH = os.environ.get('ROUTING_PBF_PATH', 'taiwan-latest.osm.pbf')
DB_PATH  = os.path.join(os.path.dirname(__file__), 'taiwan_routing_v2.sqlite')
EXT_PATH = os.environ.get('ROUTING_SPATIALITE_EXTENSION', 'mod_spatialite')

# (速度_汽車_kmh, 速度_機車_kmh, 速度_步行_kmh)
# 0 表示該模式不允許使用
SPEED_TABLE = {
    'motorway':       (110, 90,  0),
    'motorway_link':  ( 80, 60,  0),
    'trunk':          ( 90, 70,  0),
    'trunk_link':     ( 70, 50,  0),
    'primary':        ( 60, 50,  5),
    'primary_link':   ( 50, 40,  5),
    'secondary':      ( 50, 40,  5),
    'secondary_link': ( 40, 30,  5),
    'tertiary':       ( 40, 30,  5),
    'tertiary_link':  ( 30, 25,  5),
    'residential':    ( 30, 25,  5),
    'unclassified':   ( 30, 25,  5),
    'living_street':  ( 10, 10,  5),
    'service':        ( 20, 15,  5),
    'road':           ( 30, 25,  5),
    'track':          (  0,  0,  4),
    'footway':        (  0,  0,  4),
    'cycleway':       (  0,  0,  5),
    'path':           (  0,  0,  4),
    'steps':          (  0,  0,  2),
    'pedestrian':     (  0,  0,  4),
    'bridleway':      (  0,  0,  4),
}

EXPRESSWAY_HW = {'motorway', 'trunk', 'motorway_link', 'trunk_link'}

BATCH_SIZE = 10_000

ROUTE_MODES = [
    # (cost_col, data_tbl, vt_tbl, respect_oneway)
    ('cost_car',       'route_car_data',       'route_car',       True),
    ('cost_car_avoid', 'route_car_avoid_data', 'route_car_avoid', True),
    ('cost_moto',      'route_moto_data',      'route_moto',      True),
    ('cost_walk',      'route_walk_data',      'route_walk',      False),
]

DEMO_ROUTE_MODES = [
    # 展示圖忽略道路方向；步行圖原本已是雙向，無須重複建立。
    ('cost_car',       'route_car_demo_data',       'route_car_demo',       False),
    ('cost_car_avoid', 'route_car_avoid_demo_data', 'route_car_avoid_demo', False),
    ('cost_moto',      'route_moto_demo_data',      'route_moto_demo',      False),
]

# ─── 工具函數 ─────────────────────────────────────────────

def haversine_m(lon1, lat1, lon2, lat2):
    """計算兩點間距離（公尺）"""
    R = 6_371_000
    φ1, φ2 = math.radians(lat1), math.radians(lat2)
    dφ = math.radians(lat2 - lat1)
    dλ = math.radians(lon2 - lon1)
    a = math.sin(dφ/2)**2 + math.cos(φ1)*math.cos(φ2)*math.sin(dλ/2)**2
    return R * 2 * math.atan2(math.sqrt(a), math.sqrt(1-a))

def open_db(path):
    conn = sqlite3.connect(path)
    conn.enable_load_extension(True)
    conn.load_extension(EXT_PATH)
    conn.execute("PRAGMA synchronous = OFF")
    conn.execute("PRAGMA journal_mode = WAL")
    conn.execute("PRAGMA cache_size = -131072")   # 128 MB
    conn.execute("PRAGMA temp_store = MEMORY")
    return conn

# ─── Pass 1：計算 node 引用次數（找交叉路口）────────────

class NodeCounter(osmium.SimpleHandler):
    def __init__(self):
        super().__init__()
        self.node_refs = {}   # node_id → 引用次數
        self.way_count = 0

    def way(self, w):
        hw = w.tags.get('highway')
        if hw not in SPEED_TABLE:
            return
        self.way_count += 1
        for n in w.nodes:
            nid = n.ref
            self.node_refs[nid] = self.node_refs.get(nid, 0) + 1

# ─── Pass 2：建立路段 edges ───────────────────────────────

class EdgeBuilder(osmium.SimpleHandler):
    def __init__(self, intersections, conn):
        super().__init__()
        self.intersections = intersections   # set(node_id)
        self.conn = conn
        self.buf = []
        self.total = 0
        self.skipped = 0

    def way(self, w):
        hw = w.tags.get('highway')
        if hw not in SPEED_TABLE:
            return

        spd_car, spd_moto, spd_walk = SPEED_TABLE[hw]
        is_exp  = 1 if hw in EXPRESSWAY_HW else 0
        is_toll = 1 if w.tags.get('toll', 'no') == 'yes' else 0

        # 單向解析
        oneway = w.tags.get('oneway', 'no')
        oneway_ft = 1 if oneway == 'yes'  else 0
        oneway_tf = 1 if oneway == '-1'   else 0

        # 存取權限覆寫
        acc = w.tags.get('access', '')
        mva = w.tags.get('motor_vehicle', w.tags.get('motorcar', ''))
        fta = w.tags.get('foot', '')

        access_car  = 0 if acc in ('no','private') or mva in ('no','private') else (1 if spd_car>0 else 0)
        access_moto = 0 if acc in ('no','private') or mva in ('no','private') else (1 if spd_moto>0 else 0)
        access_walk = 0 if acc in ('no','private') or fta in ('no','private') else (1 if spd_walk>0 else 0)

        name = (w.tags.get('name') or w.tags.get('name:zh') or
                w.tags.get('name:en') or '')

        # 收集有效座標節點
        nodes = []
        for n in w.nodes:
            if n.location.valid():
                nodes.append((n.ref, n.location.lon, n.location.lat))

        if len(nodes) < 2:
            return

        # 在交叉路口切割路段
        seg_start = 0
        for i in range(1, len(nodes)):
            is_last  = (i == len(nodes) - 1)
            is_inter = (nodes[i][0] in self.intersections)

            if is_inter or is_last:
                seg = nodes[seg_start:i+1]
                if len(seg) >= 2:
                    self._add_edge(
                        w.id, seg, hw, name,
                        spd_car, spd_moto, spd_walk,
                        access_car, access_moto, access_walk,
                        is_exp, is_toll, oneway_ft, oneway_tf
                    )
                seg_start = i

    def _add_edge(self, way_id, seg, hw, name,
                  spd_car, spd_moto, spd_walk,
                  acc_car, acc_moto, acc_walk,
                  is_exp, is_toll, oneway_ft, oneway_tf):

        node_from = seg[0][0]
        node_to   = seg[-1][0]

        # 計算路段長度
        length_m = sum(
            haversine_m(seg[i][1], seg[i][2], seg[i+1][1], seg[i+1][2])
            for i in range(len(seg)-1)
        )
        if length_m < 0.5:
            self.skipped += 1
            return

        # LINESTRING WKT
        coords = ','.join(f'{lon} {lat}' for _, lon, lat in seg)
        wkt = f'LINESTRING({coords})'

        # 重心（bbox 快速篩選用）
        cx = sum(lon for _, lon, lat in seg) / len(seg)
        cy = sum(lat for _, lon, lat in seg) / len(seg)

        # 成本（秒）
        def cost_s(spd, length):
            if spd <= 0:
                return 9_999_999.0
            return length / (spd * 1000.0 / 3600.0)

        cost_car      = cost_s(spd_car,  length_m) if acc_car  else 9_999_999.0
        cost_moto     = cost_s(spd_moto, length_m) if acc_moto else 9_999_999.0
        cost_walk     = cost_s(spd_walk, length_m) if acc_walk else 9_999_999.0
        # 避開高速/收費：成本乘以極大懲罰值（迫使路由繞開）
        cost_car_avoid = cost_car * 999.0 if (is_exp or is_toll) else cost_car

        self.buf.append((
            way_id, node_from, node_to, name, hw,
            is_exp, is_toll, oneway_ft, oneway_tf,
            acc_car, acc_moto, acc_walk,
            round(length_m, 3),
            round(cost_car, 3), round(cost_car_avoid, 3),
            round(cost_moto, 3), round(cost_walk, 3),
            wkt, round(cx, 7), round(cy, 7)
        ))
        self.total += 1

        if len(self.buf) >= BATCH_SIZE:
            self.flush()

    def flush(self):
        if not self.buf:
            return
        self.conn.executemany("""
            INSERT INTO roads (
                osm_way_id, node_from, node_to, name, highway,
                is_expressway, is_toll, oneway_ft, oneway_tf,
                access_car, access_moto, access_walk,
                length_m, cost_car, cost_car_avoid, cost_moto, cost_walk,
                geometry, _c_x, _c_y
            ) VALUES (
                ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,
                GeomFromText(?,4326),?,?
            )
        """, self.buf)
        self.conn.commit()
        print(f'  已插入 {self.total:,} 筆', flush=True)
        self.buf = []

# ─── DB 初始化 ────────────────────────────────────────────

def init_db(conn):
    conn.execute("SELECT InitSpatialMetaData(1)")
    conn.execute("""
        CREATE TABLE roads (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            osm_way_id     INTEGER,
            node_from      INTEGER NOT NULL,
            node_to        INTEGER NOT NULL,
            name           TEXT DEFAULT '',
            highway        TEXT,
            is_expressway  INTEGER DEFAULT 0,
            is_toll        INTEGER DEFAULT 0,
            oneway_ft      INTEGER DEFAULT 0,
            oneway_tf      INTEGER DEFAULT 0,
            access_car     INTEGER DEFAULT 1,
            access_moto    INTEGER DEFAULT 1,
            access_walk    INTEGER DEFAULT 1,
            length_m       REAL,
            cost_car       REAL,
            cost_car_avoid REAL,
            cost_moto      REAL,
            cost_walk      REAL,
            _c_x           REAL,
            _c_y           REAL
        )
    """)
    conn.execute(
        "SELECT AddGeometryColumn('roads','geometry',4326,'LINESTRING','XY')"
    )
    conn.commit()

def create_indexes(conn):
    print("建立一般索引...")
    conn.execute("CREATE INDEX idx_r_node_from ON roads(node_from)")
    conn.execute("CREATE INDEX idx_r_node_to   ON roads(node_to)")
    conn.execute("CREATE INDEX idx_r_cx_cy     ON roads(_c_x, _c_y)")
    conn.commit()
    print("建立空間 RTree 索引（可能需要數分鐘）...")
    conn.execute("SELECT CreateSpatialIndex('roads','geometry')")
    conn.commit()

def build_network_tables(db_path, modes):
    import subprocess

    base_args = [
        'spatialite_network',
        '-d', db_path,
        '-T', 'roads',
        '-f', 'seq_from',
        '-t', 'seq_to',
        '-g', 'geometry',
        '-n', 'name',
        '--a-star-supported',
        '--overwrite-output',
    ]
    for cost_col, data_tbl, vt_tbl, respect_oneway in modes:
        t = time.time()
        print(f"  spatialite_network → {vt_tbl} ...", flush=True)
        args = base_args + ['-c', cost_col, '-o', data_tbl, '-vt', vt_tbl]
        if respect_oneway:
            args += ['--oneway-fromto', 'valid_ft', '--oneway-tofrom', 'valid_tf']
        else:
            args += ['--bidirectional']
        result = subprocess.run(args, capture_output=True, text=True)
        ok = 'NETWORK-DATA table' in result.stdout and 'successfully created' in result.stdout
        print(f"    {'OK' if ok else 'FAILED'}，耗時 {time.time()-t:.1f}s")
        if not ok:
            print(result.stdout[-500:])
            print(result.stderr[-500:])

def build_demo_routing(db_path):
    print("\n[建立展示用雙向 VirtualNetwork]")
    build_network_tables(db_path, DEMO_ROUTE_MODES)

def build_node_seq_and_routing(conn, db_path):
    """
    1. 刪閉合環路（spatialite_network 不接受 node_from=node_to）
    2. 加 valid_ft/valid_tf（轉換 oneway 語意為 spatialite_network 格式：1=valid, 0=forbidden）
    3. 建 node_seq 對應表（OSM node ID → 循序整數，spatialite_network 只支援 int32 範圍）
    4. 加 seq_from/seq_to 欄位
    5. 呼叫 spatialite_network CLI 建立各模式 VirtualNetwork
    """
    conn.close()   # spatialite_network 需要獨佔存取

    # 步驟 1~4 用 python 操作
    conn2 = open_db(db_path)
    conn2.execute('PRAGMA synchronous=OFF')

    print("  刪閉合環路...")
    conn2.execute('DELETE FROM roads WHERE node_from = node_to')
    conn2.commit()

    print("  加 valid_ft / valid_tf 欄位...")
    for col in ('valid_ft', 'valid_tf'):
        try:
            conn2.execute(f'ALTER TABLE roads ADD COLUMN {col} INTEGER DEFAULT 1')
        except Exception:
            pass
    conn2.execute('UPDATE roads SET valid_ft = CASE WHEN oneway_tf=1 THEN 0 ELSE 1 END')
    conn2.execute('UPDATE roads SET valid_tf = CASE WHEN oneway_ft=1 THEN 0 ELSE 1 END')
    conn2.commit()

    print("  建立 node_seq 對應表...")
    conn2.execute('DROP TABLE IF EXISTS node_seq')
    conn2.execute('CREATE TABLE node_seq (seq_id INTEGER PRIMARY KEY AUTOINCREMENT, osm_id INTEGER UNIQUE NOT NULL)')
    conn2.execute('INSERT OR IGNORE INTO node_seq (osm_id) SELECT DISTINCT node_from FROM roads')
    conn2.execute('INSERT OR IGNORE INTO node_seq (osm_id) SELECT DISTINCT node_to FROM roads')
    conn2.commit()
    cnt = conn2.execute('SELECT COUNT(*) FROM node_seq').fetchone()[0]
    print(f"    {cnt:,} 個節點")

    print("  加 seq_from / seq_to 欄位...")
    for col in ('seq_from', 'seq_to'):
        try:
            conn2.execute(f'ALTER TABLE roads ADD COLUMN {col} INTEGER')
        except Exception:
            pass
    conn2.execute('UPDATE roads SET seq_from = (SELECT seq_id FROM node_seq WHERE osm_id = node_from)')
    conn2.execute('UPDATE roads SET seq_to   = (SELECT seq_id FROM node_seq WHERE osm_id = node_to)')
    try:
        conn2.execute('CREATE INDEX idx_r_seq_from ON roads(seq_from)')
        conn2.execute('CREATE INDEX idx_r_seq_to   ON roads(seq_to)')
    except Exception:
        pass
    conn2.commit()
    conn2.close()

    # 步驟 5：呼叫 spatialite_network。
    build_network_tables(db_path, ROUTE_MODES)

    return open_db(db_path)  # 重新打開

# ─── 主程式 ───────────────────────────────────────────────

def build_roads(conn):
    t = time.time()
    print(f"\n[Pass 1] 掃描 highway 節點引用次數...")
    counter = NodeCounter()
    counter.apply_file(PBF_PATH)
    print(f"  highway ways: {counter.way_count:,}")
    print(f"  unique nodes: {len(counter.node_refs):,}")
    print(f"  耗時 {time.time()-t:.1f}s")

    intersections = {nid for nid, cnt in counter.node_refs.items() if cnt > 1}
    print(f"  intersections: {len(intersections):,}")
    del counter

    t = time.time()
    print(f"\n[Pass 2] 建立路段 edges（含節點座標）...")
    builder = EdgeBuilder(intersections, conn)
    builder.apply_file(PBF_PATH, locations=True, idx='flex_mem')
    builder.flush()
    print(f"  total edges : {builder.total:,}")
    print(f"  skipped     : {builder.skipped:,}")
    print(f"  耗時 {time.time()-t:.1f}s")

    create_indexes(conn)

def main():
    global DB_PATH, PBF_PATH, EXT_PATH
    parser = argparse.ArgumentParser(description='建立台灣路由 SQLite 資料庫')
    parser.add_argument('--roads',   action='store_true', help='只建 roads 表（跳過 VirtualNetwork）')
    parser.add_argument('--routing', action='store_true', help='只建 VirtualNetwork（roads 表須已存在）')
    parser.add_argument('--demo-routing', action='store_true', help='只建展示用雙向 VirtualNetwork（roads 表須已存在）')
    parser.add_argument('--output',  default=DB_PATH,    help=f'輸出 DB 路徑（預設 {DB_PATH}）')
    parser.add_argument('--pbf', default=PBF_PATH, help=f'OSM PBF 路徑（預設 {PBF_PATH}）')
    parser.add_argument('--extension', default=EXT_PATH, help=f'SpatiaLite extension 路徑（預設 {EXT_PATH}）')
    args = parser.parse_args()

    DB_PATH = args.output
    PBF_PATH = args.pbf
    EXT_PATH = args.extension

    if args.demo_routing:
        if args.roads or args.routing:
            parser.error('--demo-routing 不可與 --roads 或 --routing 合用')
        if not os.path.exists(DB_PATH):
            sys.exit(f"DB 不存在: {DB_PATH}，請先建立 roads 與正式路網")
        build_demo_routing(DB_PATH)
        return

    do_roads   = not args.routing
    do_routing = not args.roads
    t_all = time.time()

    if do_roads:
        if os.path.exists(DB_PATH):
            os.remove(DB_PATH)
            print(f"已刪除舊 DB: {DB_PATH}")
        conn = open_db(DB_PATH)
        print("初始化 DB...")
        init_db(conn)
        build_roads(conn)
        if not do_routing:
            conn.close()
            print(f"\n[roads 完成] 耗時 {time.time()-t_all:.1f}s")
            return
    else:
        if not os.path.exists(DB_PATH):
            sys.exit(f"DB 不存在: {DB_PATH}，請先執行 --roads 步驟")
        conn = open_db(DB_PATH)
        print(f"開啟既有 DB: {DB_PATH}")

    if do_routing:
        print("\n[建立 VirtualNetwork]")
        conn = build_node_seq_and_routing(conn, DB_PATH)

    conn.close()
    elapsed = time.time() - t_all
    size_mb = os.path.getsize(DB_PATH) / 1024**2
    print(f"\n✅ 全部完成！總耗時 {elapsed:.1f}s")
    print(f"   DB 大小: {size_mb:.1f} MB")
    print(f"   路徑: {DB_PATH}")

if __name__ == '__main__':
    main()
