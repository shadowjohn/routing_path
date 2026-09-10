# 路線規劃系統教學

## 架構

```
Taiwan OSM PBF
  └─ build_routing_v2.py
       └─ taiwan_routing_v2.sqlite          # 產生物，不進 Git
            ├─ roads
            ├─ route_car
            ├─ route_car_avoid
            ├─ route_moto
            ├─ route_walk
            ├─ route_car_demo
            ├─ route_car_avoid_demo
            └─ route_moto_demo

index.php ── api.php ── SpatiaLite
     └──── FocusIT Easymap 7117 CDN
```

`routing_config.php` 提供可攜的預設值；`config.local.php` 可覆寫資料庫、SpatiaLite extension、Easymap 與地理編碼服務位置，且永不提交。

## 建立路網資料庫

### 需求

```bash
pip3 install osmium
sudo apt install spatialite-bin
```

PHP 執行環境也需要啟用 `pdo_sqlite` 與可載入 SpatiaLite extension。

### 取得資料並建庫

從 [Geofabrik Taiwan](https://download.geofabrik.de/asia/taiwan.html) 下載 PBF 後，在專案根目錄執行：

```bash
python3 build_routing_v2.py \
  --pbf taiwan-latest.osm.pbf \
  --extension /path/to/mod_spatialite.so
```

可用的參數：

```text
--roads                 只建立 roads 表
--routing               對既有 DB 建立 VirtualNetwork
--demo-routing          對既有 DB 建立展示用雙向 VirtualNetwork
--output FILE           路由 DB 輸出位置
--pbf FILE              OSM PBF 來源
--extension FILE        SpatiaLite extension
```

腳本會先把 OSM way 拆成路段，再建立下列資料：

- `roads`：幾何、各交通模式成本、單行道與存取權限。
- `node_seq`：把超出 int32 的 OSM node ID 重新映射為循序整數。
- `idx_roads_geometry`：最近道路節點使用的 RTree 空間索引。
- 四個 VirtualNetwork 路由表：汽車、避高速／收費汽車、機車、步行。
- 三個展示用雙向圖：汽車、避高速／收費汽車、機車；步行圖原本已是雙向，所以共用 `route_walk`。

`cost_car_avoid` 會將高速與收費路段成本乘上 999，以優先繞開，而不是讓路網斷裂。

## 設定與啟動

複製設定範本：

```bash
cp config.local.php.example config.local.php
```

最小設定如下；`database_path` 未指定時會使用專案根目錄的 `taiwan_routing_v2.sqlite`。

```php
<?php

return [
    'easymap_script' => 'https://tile.focusit.tw/Easymap7117/easymap.js',
    'spatialite_extension' => '/path/to/mod_spatialite.so',
    'address_api_url' => '', // 留空時僅支援 lon,lat 輸入
];
```

預設會從 FocusIT 的 Easymap 7117 CDN 載入 SDK。若需使用自己的版本，可在 `config.local.php` 覆寫 `easymap_script`。

SQLite VirtualNetwork 查詢會建立暫存檔，因此路由 DB 與所在目錄必須可由 PHP 執行帳號寫入。請以 web service 帳號的擁有者／群組權限設定資料目錄，避免開放所有人寫入。

本機啟動：

```bash
php -S 127.0.0.1:8000
```

- 地圖介面：`http://127.0.0.1:8000/index.php`
- API 測試頁：`http://127.0.0.1:8000/api_tester.php`

## API

Endpoint：`api.php?mode=routing_path`

| 參數 | 說明 | 範例 |
|---|---|---|
| `start_point` | 起點 WGS84 `lon,lat` | `120.665689,24.119797` |
| `end_point` | 終點 WGS84 `lon,lat` | `120.649321,24.180852` |
| `passpath` | 途徑點；每行一組 `lon,lat` | 選填 |
| `travel_mode` | `car`、`moto`、`walk` | `moto` |
| `direction_policy` | `legal`（預設，合法導航）或 `demo_bidirectional`（僅展示稽核，忽略單行方向） | `legal` |
| `avoid_highway` | 1 為避開高速／快速道路 | `0` |
| `avoid_toll` | 1 為避開收費路段 | `0` |

前端以 POST 呼叫 API，因此後端採用 `$_REQUEST`；GET 與 POST 都能使用。

```bash
curl 'http://127.0.0.1:8000/api.php?mode=routing_path&start_point=120.665689,24.119797&end_point=120.649321,24.180852&travel_mode=moto'
```

成功回應包含：

- `summary`：時間、距離、模式與路由表。
- `snap_points`：起點、終點與途徑點吸附到路網的位置及距離。
- `route_parts`：可直接繪製的 WKT 路線。
- `segments`：依連續道路名稱彙整的導航摘要。
- `direction_policy`、`is_demo_only`、`contains_reverse_edges`、`reversed_edge_count`：方向模式與逆向展示的可追溯資訊。展示模式絕不可用於正式導航、施工或接管判定。

圖路徑不存在時，回應為 `status: "NO_PATH"`，不會以直線補畫。

## 前端行為

- 可直接輸入 `lon,lat`，或按「選點」後點選地圖。
- 地址、地標與 POI 功能需設定相容的 `address_api_url`；未設定時不影響座標路由。
- 右鍵選單用原生 `contextmenu` 事件保留像素座標，再呼叫 Easymap 的 `revXY()` 轉為經緯度。
- 地圖容器明確使用桌機 360px 側欄與手機 300px 上方面板，初始化及 resize 後會呼叫 `map.resize()`。
- 路線、接駁虛線、播放 marker、語音與可分享 URL 都在 `index.php` 的前端程式完成。

## 驗證

```bash
php -l api.php
php -l index.php
php tests/direction_policy.test.php
node tests/geocode_ui.test.js
```

若路由 DB 已建好，再執行前述 `curl`；回應的 `status` 應為 `OK`，且 `route_parts` 至少有一段 WKT。

## 常見問題

| 現象 | 原因與處理 |
|---|---|
| 找不到資料庫 | 檢查 `database_path` 與 DB 是否已由建庫腳本產生。 |
| 無法載入 SpatiaLite | 在 `config.local.php` 設定正確的 `spatialite_extension`，並確認 PHP SQLite 可載入 extension。 |
| `readonly database` | 讓 PHP 執行帳號能寫入 DB 和其目錄；不要以 `chmod 777` 解決。 |
| 地圖空白 | 確認可連線至 Easymap CDN，或在 `config.local.php` 設定自己的 `easymap_script` URL。 |
| 地址查詢不可用 | 設定可回傳相容 JSON 的 `address_api_url`，或直接使用 `lon,lat`。 |
