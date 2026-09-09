# routing_path

台灣 OSM + SpatiaLite 的 PHP 路線規劃範例。支援汽車、機車、步行、途徑點與可選的地址／POI 查詢。

資料庫與部署設定都不納入版本控制；Easymap 7117 預設從 FocusIT CDN 載入，因此可安全公開這個 MIT 專案。

## 快速開始

需求：PHP 的 `pdo_sqlite`、SpatiaLite、Python 3、`pyosmium`，以及 `spatialite_network` 指令。

```bash
pip3 install osmium
wget https://download.geofabrik.de/asia/taiwan-latest.osm.pbf
python3 build_routing_v2.py \
  --pbf taiwan-latest.osm.pbf \
  --extension /path/to/mod_spatialite.so
```

接著建立本機設定並啟動 PHP：

```bash
cp config.local.php.example config.local.php
php -S 127.0.0.1:8000
```

開啟 `http://127.0.0.1:8000/index.php`；API 測試頁為 `api_tester.php`。

## 本機設定

`config.local.php` 不會被 Git 追蹤。通常只要指定本機 SpatiaLite 路徑；地址與 POI 服務是選用功能。

```php
return [
    'easymap_script' => 'https://tile.focusit.tw/Easymap7117/easymap.js',
    'spatialite_extension' => '/path/to/mod_spatialite.so',
    'address_api_url' => 'https://your-service.example/api.php',
];
```

路由資料庫預設為專案根目錄的 `taiwan_routing_v2.sqlite`，也可用 `database_path` 覆寫。SQLite VirtualNetwork 查詢會建立暫存檔，資料庫檔案與所在目錄必須由 PHP 執行帳號寫入；請以擁有者與群組權限設定，不要使用 `chmod 777`。

## 驗證

```bash
php -l api.php
php test.php
node tests/geocode_ui.test.js
curl 'http://127.0.0.1:8000/api.php?mode=routing_path&start_point=120.665689,24.119797&end_point=120.649321,24.180852&travel_mode=moto'
```

完整架構、建庫與 API 說明見 [teach.md](teach.md)；變更記錄見 [history.md](history.md)。

## License

本專案採 MIT License。Easymap SDK 由 CDN 提供，OSM 資料分別適用其原有授權與使用條款。
