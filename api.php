<?php
require_once __DIR__ . '/routing_config.php';

header('Content-Type: application/json; charset=utf-8');

function routing_json_error($msg, $extra = [])
{
  echo json_encode(array_merge([
    'status' => 'ERROR',
    'msg'    => $msg,
  ], $extra), JSON_UNESCAPED_UNICODE);
  exit();
}

function routing_select(PDO $pdo, string $sql, array $params): array
{
  $statement = $pdo->prepare($sql);
  $statement->execute($params);
  return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function routing_parse_xy($value, $name)
{
  $parts = explode(',', trim($value ?? ''));
  if (count($parts) < 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
    routing_json_error("{$name} 格式錯誤，請使用 lon,lat");
  }

  $lon = (double)$parts[0];
  $lat = (double)$parts[1];
  if ($lon < 119 || $lon > 123 || $lat < 21 || $lat > 26) {
    routing_json_error("{$name} 超出台灣常用經緯度範圍");
  }

  return [$lon, $lat];
}

function routing_haversine_m($lon1, $lat1, $lon2, $lat2)
{
  $r = 6371000.0;
  $p1 = deg2rad($lat1);
  $p2 = deg2rad($lat2);
  $dp = deg2rad($lat2 - $lat1);
  $dl = deg2rad($lon2 - $lon1);
  $a = sin($dp / 2) * sin($dp / 2) +
       cos($p1) * cos($p2) * sin($dl / 2) * sin($dl / 2);
  return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

function routing_wkt_points($wkt)
{
  $points = [];
  if (!is_string($wkt) || $wkt === '') return $points;

  preg_match_all('/(-?\d+(?:\.\d+)?)\s+(-?\d+(?:\.\d+)?)/', $wkt, $m, PREG_SET_ORDER);
  foreach ($m as $p) {
    $points[] = [(double)$p[1], (double)$p[2]];
  }
  return $points;
}

function routing_wkt_length_m($wkt)
{
  $points = routing_wkt_points($wkt);
  $len = 0.0;
  for ($i = 1, $max = count($points); $i < $max; $i++) {
    $len += routing_haversine_m($points[$i - 1][0], $points[$i - 1][1], $points[$i][0], $points[$i][1]);
  }
  return $len;
}

function routing_find_near_node($pdo, $lon, $lat, $access_col, $kind)
{
  $node_col = $kind === 'to' ? 'node_to' : 'node_from';
  $seq_col  = $kind === 'to' ? 'seq_to'  : 'seq_from';
  $radii = [0.003, 0.01, 0.03, 0.08];
  $point = "POINT({$lon} {$lat})";

  foreach ($radii as $radius) {
    $sql = "
      SELECT
        r.{$node_col} AS node_id,
        r.{$seq_col}  AS seq_id,
        r._c_x        AS snapped_lon,
        r._c_y        AS snapped_lat,
        ST_Distance(GeomFromText(?,4326), r.geometry, 1) AS distance_m
      FROM idx_roads_geometry idx
      JOIN roads r ON r.id = idx.pkid
      WHERE idx.xmin <= ? AND idx.xmax >= ?
        AND idx.ymin <= ? AND idx.ymax >= ?
        AND r.{$access_col}=1
      ORDER BY distance_m ASC
      LIMIT 1
    ";
    $pa = [$point, $lon + $radius, $lon - $radius, $lat + $radius, $lat - $radius];

    try {
      $rows = routing_select($pdo, $sql, $pa);
    } catch (Throwable $ex) {
      $rows = [];
    }
    if (!empty($rows)) {
      $rows[0]['search_radius_deg'] = $radius;
      return $rows[0];
    }
  }

  // RTree 不可用時的 fallback，保留舊中心點 bbox 策略。
  $sql = "
    SELECT
      {$node_col} AS node_id,
      {$seq_col}  AS seq_id,
      _c_x        AS snapped_lon,
      _c_y        AS snapped_lat,
      ST_Distance(GeomFromText(?,4326), geometry, 1) AS distance_m
    FROM roads
    WHERE _c_x BETWEEN ? AND ?
      AND _c_y BETWEEN ? AND ?
      AND {$access_col}=1
    ORDER BY distance_m ASC
    LIMIT 1
  ";
  $rows = routing_select($pdo, $sql, [$point, $lon - 0.08, $lon + 0.08, $lat - 0.08, $lat + 0.08]);
  if (!empty($rows)) {
    $rows[0]['search_radius_deg'] = 0.08;
    return $rows[0];
  }

  return null;
}

function routing_compact_steps($rows)
{
  $steps = [];
  foreach ($rows as $row) {
    $name = trim($row['name'] ?? '');
    if ($name === '') $name = '未命名道路';
    $cost = (float)($row['Cost'] ?? 0);
    if (empty($steps) || $steps[count($steps) - 1]['name'] !== $name) {
      $steps[] = [
        'name'     => $name,
        'cost_sec' => $cost,
        'count'    => 1,
      ];
    } else {
      $steps[count($steps) - 1]['cost_sec'] += $cost;
      $steps[count($steps) - 1]['count']++;
    }
  }

  foreach ($steps as $i => $step) {
    $steps[$i]['cost_sec'] = round($step['cost_sec'], 1);
    $steps[$i]['cost_min'] = round($step['cost_sec'] / 60, 1);
  }
  return $steps;
}

$mode = $_REQUEST['mode'] ?? '';
switch($mode)
{
  case 'routing_path':
    {
      [$slon, $slat] = routing_parse_xy($_REQUEST['start_point'] ?? '', 'start_point');
      [$elon, $elat] = routing_parse_xy($_REQUEST['end_point'] ?? '', 'end_point');

      $passpath = trim($_REQUEST['passpath'] ?? '');
      $pp = $passpath !== '' ? explode("\n", $passpath) : [];

      $travel_mode = in_array($_REQUEST['travel_mode'] ?? '', ['car','moto','walk'])
                     ? $_REQUEST['travel_mode'] : 'car';
      $avoid = (intval($_REQUEST['avoid_highway'] ?? 0) || intval($_REQUEST['avoid_toll'] ?? 0)) ? 1 : 0;

      $route_table_map = [
        'car'  => $avoid ? 'route_car_avoid' : 'route_car',
        'moto' => 'route_moto',
        'walk' => 'route_walk',
      ];
      $route_table = $route_table_map[$travel_mode];
      $access_col  = "access_{$travel_mode}";

      $config = routing_config();
      $db_v2 = $config['database_path'];
      if (!is_file($db_v2)) routing_json_error('找不到路由資料庫，請設定 config.local.php');

      try {
        $thepdo = new PDO("sqlite:{$db_v2}");
        $thepdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $thepdo->query('SELECT load_extension(' . $thepdo->quote($config['spatialite_extension']) . ');');
        $thepdo->exec('PRAGMA synchronous = off;');
        $thepdo->exec('PRAGMA cache_size = -32768;');
      } catch (Throwable $ex) {
        routing_json_error('無法載入 SpatiaLite 或開啟路由資料庫');
      }

      $start_row = routing_find_near_node($thepdo, $slon, $slat, $access_col, 'from');
      if (!$start_row) routing_json_error('起點附近找不到路網節點');

      $via_seqs = [];
      $snap_points = [
        'start' => $start_row,
        'via'   => [],
      ];
      foreach ($pp as $v) {
        $v = trim($v);
        if ($v === '') continue;
        [$mlon, $mlat] = routing_parse_xy($v, 'passpath');
        $r = routing_find_near_node($thepdo, $mlon, $mlat, $access_col, 'from');
        if ($r) {
          $via_seqs[] = (int)$r['seq_id'];
          $snap_points['via'][] = $r;
        }
      }

      $end_row = routing_find_near_node($thepdo, $elon, $elat, $access_col, 'to');
      if (!$end_row) routing_json_error('終點附近找不到路網節點');
      $snap_points['end'] = $end_row;

      $seq_nodes = array_merge(
        [(int)$start_row['seq_id']],
        $via_seqs,
        [(int)$end_row['seq_id']]
      );

      $mSQL = [];
      for ($i = 0, $max = count($seq_nodes) - 1; $i < $max; $i++) {
        $nf = (int)$seq_nodes[$i];
        $nt = (int)$seq_nodes[$i + 1];
        $mSQL[] = "
          SELECT
            '{$i}' AS ROAD_NUM,
            Algorithm,
            NodeFrom,
            NodeTo,
            Cost,
            ASWKT(Geometry) AS wkt,
            Name AS name
          FROM {$route_table}
          WHERE NodeFrom={$nf} AND NodeTo={$nt}
        ";
      }

      if (empty($mSQL)) routing_json_error('路由節點不足');
      $ra = routing_select($thepdo, implode(" UNION ALL ", $mSQL), []);

      $rb = [];
      $route_parts = [];
      $steps_src = [];
      $total_cost_s = 0.0;
      $total_length_m = 0.0;

      foreach ($ra as $row) {
        if ($row['wkt'] === null && $row['Cost'] === null) continue;
        $seg = $row['ROAD_NUM'];
        if (!isset($rb[$seg])) $rb[$seg] = [];
        $rb[$seg][] = $row;

        if (is_string($row['wkt']) && $row['wkt'] !== '') {
          $route_parts[$seg] = [
            'road_num' => (int)$seg,
            'cost_sec' => (float)($row['Cost'] ?? 0),
            'wkt'      => $row['wkt'],
          ];
          $total_cost_s += (float)($row['Cost'] ?? 0);
          $total_length_m += routing_wkt_length_m($row['wkt']);
        } else {
          $steps_src[] = $row;
        }
      }

      ksort($route_parts);
      $route_parts = array_values($route_parts);

      echo json_encode([
        'status'          => 'OK',
        'travel_mode'     => $travel_mode,
        'avoid'           => (bool)$avoid,
        'route_table'     => $route_table,
        'total_cost_sec'  => round($total_cost_s),
        'total_cost_min'  => round($total_cost_s / 60, 1),
        'total_length_m'  => round($total_length_m, 1),
        'total_length_km' => round($total_length_m / 1000, 2),
        'summary'         => [
          'travel_mode'     => $travel_mode,
          'avoid'           => (bool)$avoid,
          'route_table'     => $route_table,
          'total_cost_sec'  => round($total_cost_s),
          'total_cost_min'  => round($total_cost_s / 60, 1),
          'total_length_m'  => round($total_length_m, 1),
          'total_length_km' => round($total_length_m / 1000, 2),
        ],
        'snap_points'     => $snap_points,
        'route_parts'     => $route_parts,
        'segments'        => routing_compact_steps($steps_src),
        'nodes'           => $seq_nodes,
        'data'            => $rb,
      ], JSON_UNESCAPED_UNICODE);
      exit();
    }
    break;
  default:
    routing_json_error('未知的 mode');
}
