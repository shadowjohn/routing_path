# routing_path 開發歷程

本檔案記錄程式與架構的重要變更；部署路徑、私有服務 URL 與本機設定不放入版本控制。

## 2026-09-10 — 展示用雙向道路路由

- `direction_policy=legal|demo_bidirectional`：預設合法路由完全維持原狀；展示模式改用預建的雙向 VirtualNetwork。
- 展示回應會標示 `is_demo_only`、`contains_reverse_edges`、`reversed_edge_count`，並在 `segments` 標示逆向展示路段。
- 無法連通的路網明確回傳 `NO_PATH`，不回傳可被前端誤繪的直線。
- 建庫腳本新增 `--demo-routing`，只新增展示圖而不重建既有合法路網。

## 2026-09-09 — 公開 MIT 初版

- 建立 GitHub `main` 首版提交，延續 MIT License。
- 加入 `.gitignore`：忽略路由 SQLite、SQLite 暫存檔、`config.local.php` 與本機工具檔案。
- 加入 `routing_config.php` 與 `config.local.php.example`：資料庫、SpatiaLite、Easymap 與地址服務都能用本機設定覆寫。
- 移除舊框架的全域 config、header、AJAX helper 與固定檔案系統路徑。
- `api.php` 改用 PDO prepared statement；不再依賴專案外的資料庫 helper。
- `index.php` 與 `api_tester.php` 改為專案內的 HTML head/body 與 jQuery AJAX helper；移除頂部框架 navbar 的版面補償。
- `build_routing_v2.py` 改用 `--pbf`、`--extension` 參數與環境變數預設，建庫不再綁定特定主機路徑。
- Easymap 預設改由 FocusIT 的 7117 CDN 載入；仍可在本機設定覆寫。
- 實測通過 PHP lint、前端 geocode 測試與 SpatiaLite 路由 smoke test。

## 2026-05-09 — 最近節點與互動介面

- 最近節點查詢改用 `idx_roads_geometry` RTree，依 `0.003 → 0.01 → 0.03 → 0.08` 度半徑擴大搜尋，並保留 bbox fallback。
- 加入台灣範圍的座標驗證，支援起點、終點與多個途徑點。
- 修正 VirtualNetwork 總成本重複加總：只使用帶完整 WKT 的總覽列計入總成本。
- 回應新增 `summary`、`snap_points`、`route_parts`、`segments` 與總距離，並保留舊 `data` 欄位。
- 介面改為左側規劃面板、地圖選點、右鍵選單、接駁虛線、播放、語音與可分享 URL。

## 2026-05-08 — v2 路網建立

- 以台灣 OSM PBF 建立 `taiwan_routing_v2.sqlite`。
- 使用兩階段 pyosmium 解析：先統計路口，再將 way 切為可路由路段。
- 建立 `roads`、`node_seq`、RTree 空間索引與四個 VirtualNetwork 路由表。
- 支援汽車、避高速／收費汽車、機車與步行；單行道與不同行駛模式的成本分別處理。
- 透過 `node_seq` 將超出 int32 的 OSM node ID 映射為 VirtualNetwork 可使用的整數。

## 2024-04-08 — 環境連線檢查

- 加入本機 SQLite + SpatiaLite 連線診斷。

## 2023-05-30 — 原型

- 建立 `routing_path` API 原型。
- 支援起點、終點與以換行分隔的途徑點。
