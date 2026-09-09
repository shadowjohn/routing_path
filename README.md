# routing_path

台灣 OSM + SpatiaLite 的 PHP 路線規劃範例，支援汽車、機車、步行與途徑點。

## Setup

1. Install PHP SQLite, SpatiaLite, Python 3, and `pyosmium`.
2. Download a Taiwan OSM PBF, then build the routing DB:

   ```bash
   python3 build_routing_v2.py --pbf taiwan-latest.osm.pbf --extension /path/to/mod_spatialite.so
   ```

3. Copy `config.local.php.example` to `config.local.php` and set the local SpatiaLite path. Configure the geocoding URL only if address and POI lookup are needed.
4. Put a separately licensed Easymap distribution in `assets/easymap/`, or set `easymap_script` to its served URL. Easymap is not included in this repository.

The generated SQLite database and local configuration are intentionally ignored by Git.

## License

MIT. The Easymap SDK and OSM data have their own licenses and terms.
