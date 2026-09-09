<?php
  $base_dir = __DIR__;
  require "{$base_dir}/html.php";
  require "{$base_dir}/head.php";
?>
<title>路線規劃</title>
<style>
html, body { margin:0; padding:0; overflow:hidden; background:#0f172a; }
body { background:#0f172a; color:#e5e7eb; }
#route-app { position:fixed; top:0; left:0; right:0; bottom:0; width:100vw; height:100vh; overflow:hidden; background:#0f172a; }
#map {
  position:absolute; top:0; left:360px;
  width:calc(100vw - 360px);
  height:100vh;
  min-width:320px;
  min-height:320px;
  background:#111827;
}
#route-panel {
  position:absolute; top:0; left:0; bottom:0; width:360px; height:100vh; z-index:1001;
  background:#111827; border-right:1px solid #334155; box-shadow:0 2px 16px rgba(0,0,0,.42);
  display:flex; flex-direction:column;
}
.route-head { padding:14px 16px 10px; background:#020617; color:#f8fafc; border-bottom:1px solid #334155; }
.route-head h3 { margin:0; font-size:18px; font-weight:700; }
.route-head .hint { color:#94a3b8; font-size:12px; margin-top:4px; }
.route-body { padding:12px 14px; overflow:hidden; flex:1; display:flex; flex-direction:column; }
.field-row { margin-bottom:10px; position:relative; }
.field-label { font-size:12px; font-weight:700; color:#cbd5e1; margin-bottom:4px; display:flex; justify-content:space-between; }
.field-label .point-quality { color:#93c5fd; font-weight:600; max-width:170px; text-align:right; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
.coord-line { display:flex; gap:6px; }
.coord-line input {
  height:32px; border:1px solid #475569; border-radius:4px; padding:4px 8px;
  font-size:13px; flex:1; min-width:0; background:#0f172a; color:#e5e7eb;
}
.coord-line input::placeholder, .via-item input::placeholder { color:#64748b; }
.coord-line input:focus, .via-item input:focus {
  outline:none; border-color:#60a5fa; box-shadow:0 0 0 2px rgba(96,165,250,.22);
}
.coord-line input.geo-error, .via-item input.geo-error { border-color:#ef4444; box-shadow:0 0 0 2px rgba(239,68,68,.22); }
.mini-btn, .main-btn, .ghost-btn {
  border:1px solid #475569; background:#1e293b; color:#e5e7eb; border-radius:4px;
  height:32px; padding:0 10px; font-size:13px; cursor:pointer;
}
.mini-btn:hover, .ghost-btn:hover { background:#334155; border-color:#64748b; }
.main-btn { width:100%; background:#2563eb; color:#fff; border-color:#2563eb; font-weight:700; }
.main-btn:hover { background:#1d4ed8; }
.danger-btn { border-color:#ef4444; color:#fecaca; }
.danger-btn:hover { background:#7f1d1d; border-color:#ef4444; color:#fff; }
.mode-row { display:flex; gap:6px; margin:10px 0; }
.mode-btn { flex:1; height:34px; border:1px solid #475569; background:#1e293b; color:#cbd5e1; border-radius:4px; cursor:pointer; }
.mode-btn:hover { background:#334155; }
.mode-btn.active { background:#2563eb; color:#fff; border-color:#2563eb; font-weight:700; }
.option-row { display:flex; gap:12px; margin:8px 0 12px; font-size:13px; color:#cbd5e1; }
.option-row label { cursor:pointer; }
.via-item { display:flex; gap:6px; margin-top:6px; }
.via-item input { flex:1; height:30px; border:1px solid #475569; border-radius:4px; padding:3px 8px; font-size:12px; background:#0f172a; color:#e5e7eb; }
.geo-suggest {
  display:none; position:absolute; left:0; right:0; top:52px; z-index:1300;
  border:1px solid #475569; border-radius:6px; overflow:hidden;
  background:#020617; box-shadow:0 12px 24px rgba(0,0,0,.38);
}
.via-row .geo-suggest { top:58px; }
.geo-option {
  display:block; width:100%; border:0; border-bottom:1px solid #1e293b; background:#020617;
  color:#e5e7eb; text-align:left; padding:8px 10px; cursor:pointer;
}
.geo-option:last-child { border-bottom:0; }
.geo-option:hover { background:#1e293b; }
.geo-title { display:block; font-size:13px; font-weight:700; color:#f8fafc; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
.geo-subtitle { display:block; margin-top:2px; font-size:12px; color:#94a3b8; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
.geo-source { display:block; margin-top:3px; font-size:11px; color:#93c5fd; }
.result-box { border-top:1px solid #334155; margin-top:14px; padding-top:12px; display:none; }
#result-box[style*="display: block"] { display:flex !important; flex-direction:column; flex:1; min-height:0; }
.summary-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.summary-card { border:1px solid #334155; border-radius:6px; padding:8px; background:#0f172a; }
.summary-card .k { color:#94a3b8; font-size:12px; }
.summary-card .v { color:#f8fafc; font-weight:700; font-size:16px; margin-top:2px; }
.steps-list { margin:10px 0 0; padding:0; list-style:none; flex:1; overflow-y:auto; min-height:0; border:1px solid #334155; border-radius:6px; background:#0f172a; }
.steps-list li {
  padding:7px 9px; border-bottom:1px solid #334155; font-size:12px; color:#cbd5e1;
  display:flex; align-items:flex-start; gap:8px; scroll-margin:14px;
}
.steps-list li:last-child { border-bottom:none; }
.steps-list li .step-icon {
  width:18px; height:18px; line-height:17px; border-radius:50%; flex:0 0 18px;
  text-align:center; border:1px solid #64748b; color:#94a3b8; font-size:11px; margin-top:1px;
}
.steps-list li .step-main { flex:1; min-width:0; }
.steps-list li .step-name { color:#e5e7eb; font-weight:600; }
.steps-list li .step-meta { color:#94a3b8; margin-top:2px; }
.steps-list li.done { background:rgba(22,163,74,.12); color:#bbf7d0; }
.steps-list li.done .step-icon { background:#16a34a; border-color:#22c55e; color:#fff; }
.steps-list li.active { background:rgba(37,99,235,.24); box-shadow:inset 3px 0 0 #60a5fa; }
.steps-list li.active .step-icon { background:#2563eb; border-color:#60a5fa; color:#fff; }
.playbar { display:flex; gap:6px; margin-top:10px; }
.playbar button { flex:1; }
#status-line { position:absolute; left:380px; right:20px; bottom:18px; z-index:1000; pointer-events:none; }
#status-text { display:inline-block; background:rgba(2,6,23,.9); color:#f8fafc; border:1px solid rgba(148,163,184,.35); border-radius:4px; padding:8px 10px; font-size:13px; }
#loading {
  display:none; position:fixed; top:0; left:0; right:0; bottom:0; z-index:2000;
  background:rgba(2,6,23,.72); color:#fff; align-items:center; justify-content:center; font-size:18px;
}
#loading.show { display:flex; }
@media (max-width:768px) {
  #route-app { top:0; width:100vw; height:100vh; }
  #route-panel { width:100vw; height:300px; bottom:auto; border-right:none; border-bottom:1px solid #334155; }
  #map {
    left:0; top:300px;
    width:100vw;
    height:calc(100vh - 300px);
    min-height:260px;
  }
  #status-line { left:10px; right:10px; bottom:12px; }
  .route-body { padding-bottom:18px; display:block !important; overflow:auto !important; }
  #result-box[style*="display: block"] { display:block !important; flex:none !important; }
  .steps-list { max-height:110px !important; min-height:auto !important; flex:none !important; overflow:auto !important; }
}
/* pulsing GPS marker style */
@keyframes gpsPulse {
  0% { transform: scale(0.5); opacity: 1; }
  100% { transform: scale(1.8); opacity: 0; }
}
.my-location-pulse {
  position: absolute; width: 24px; height: 24px; border: 3px solid #fff; border-radius: 50%;
  background: #0ea5e9; box-shadow: 0 0 8px rgba(14,165,233,0.6); animation: gpsPulse 2s infinite ease-out;
}
.my-location-dot {
  position: absolute; width: 12px; height: 12px; border: 2px solid #fff; border-radius: 50%; background: #0ea5e9;
}
/* premium popup style */
.premium-popup { font-family: Inter, system-ui, -apple-system, sans-serif !important; }
.popup-title { font-size: 14px; font-weight: 700; color: #1a73e8 !important; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
.popup-tabs { display: flex; border-bottom: 2px solid #e8eaed; margin-bottom: 10px; gap: 4px; padding-bottom: 2px; }
.popup-tab { padding: 6px 12px; font-size: 12px; cursor: pointer; border-radius: 6px; background: #f1f3f4; color: #5f6368 !important; font-weight: 600; display: flex; align-items: center; gap: 4px; border: 1px solid #dadce0; transition: all 0.2s ease; }
.popup-tab:hover { background: #e8eaed; color: #202124 !important; }
.popup-tab.active { background: #1a73e8; color: #ffffff !important; border-color: #1a73e8; box-shadow: 0 2px 4px rgba(26,115,232,0.3); }
.popup-panel { display: none; max-height: 240px; overflow-y: auto; }
.popup-panel.active { display: block; }
.poi-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; border-bottom: 1px solid #f1f3f4; cursor: pointer; border-radius: 6px; margin-bottom: 4px; transition: all 0.2s ease; border: 1px solid transparent; }
.poi-item:hover { background: #e8f0fe; border-color: #d2e3fc; }
.poi-name { font-size: 13px; font-weight: 700; color: #202124 !important; text-align: left; }
.poi-addr { font-size: 11px; color: #5f6368 !important; margin-top: 2px; text-align: left; }
.poi-dist { font-size: 11px; font-weight: 700; color: #1a73e8 !important; background: #e8f0fe; padding: 2px 8px; border-radius: 9999px; border: 1px solid #d2e3fc; }
.poi-loading { font-size: 12px; color: #70757a !important; padding: 16px 0; text-align: center; font-weight: 500; }
/* Fix Easymap popup text color inheritance globally */
.easymap-popup, .easymap-popup-content, .easymap-popup-title, .easymap-popup * {
  color: #202124 !important;
}
/* Ensure links or closer in popup remains dark and clickable */
.easymap-popup-closer {
  color: #70757a !important;
}
/* Allow premium popup sub-elements to retain their custom contrast colors */
.easymap-popup .poi-dist, .easymap-popup .poi-dist * {
  color: #1a73e8 !important;
}
.easymap-popup .popup-tab.active, .easymap-popup .popup-tab.active * {
  color: #ffffff !important;
}
.easymap-popup .popup-tab:not(.active), .easymap-popup .popup-tab:not(.active) * {
  color: #5f6368 !important;
}
.easymap-popup .poi-addr {
  color: #70757a !important;
}
.easymap-popup .poi-loading {
  color: #70757a !important;
}
</style>
<?php
  require "{$base_dir}/head_end.php";
  require "{$base_dir}/body.php";
?>

<div id="route-app">
  <div id="route-panel">
    <div class="route-head">
      <div style="display:flex;align-items:center;justify-content:space-between;">
        <h3>路線規劃</h3>
        <a href="api_tester.php" target="_blank" style="font-size:12px;color:#60a5fa;text-decoration:none;border:1px solid #334155;border-radius:4px;padding:2px 8px;">API 測試</a>
      </div>
      <div class="hint">輸入地址、地標或 lon,lat，或按選點後點地圖</div>
    </div>
    <div class="route-body">
      <div class="field-row">
        <div class="field-label"><span>起點</span><span id="start-snap" class="point-quality"></span></div>
        <div class="coord-line">
          <input id="inp-start" type="text" value="120.665689,24.119797" placeholder="地址、地標或 120.649834,24.177558" autocomplete="off">
          <button class="mini-btn" data-pick="start">選點</button>
        </div>
        <div id="suggest-start" class="geo-suggest"></div>
      </div>
      <div id="via-list"></div>
      <div class="field-row">
        <button class="ghost-btn" id="btn-add-via" type="button">新增途徑點</button>
      </div>
      <div class="field-row">
        <div class="field-label"><span>終點</span><span id="end-snap" class="point-quality"></span></div>
        <div class="coord-line">
          <input id="inp-end" type="text" value="120.649321,24.180852" placeholder="地址、地標或 120.665589,24.119477" autocomplete="off">
          <button class="mini-btn" data-pick="end">選點</button>
        </div>
        <div id="suggest-end" class="geo-suggest"></div>
      </div>

      <div class="mode-row">
        <button class="mode-btn" data-mode="car" type="button">汽車</button>
        <button class="mode-btn active" data-mode="moto" type="button">機車</button>
        <button class="mode-btn" data-mode="walk" type="button">步行</button>
      </div>
      <div class="option-row">
        <label><input type="checkbox" id="chk-avoid-hw"> 避高速</label>
        <label><input type="checkbox" id="chk-avoid-toll"> 避收費</label>
        <label><input type="checkbox" id="chk-voice" checked> 語音</label>
      </div>

      <button id="btn-route" class="main-btn" type="button">開始規劃</button>
      <div class="playbar">
        <button id="btn-play" class="ghost-btn" type="button">播放</button>
        <button id="btn-pause" class="ghost-btn" type="button">暫停</button>
        <button id="btn-stop" class="ghost-btn danger-btn" type="button">停止</button>
      </div>
      <div class="playbar">
        <button id="btn-clear" class="ghost-btn danger-btn" type="button">清除</button>
      </div>

      <div id="result-box" class="result-box">
        <div class="summary-grid">
          <div class="summary-card"><div class="k">預估時間</div><div class="v" id="sum-time">-</div></div>
          <div class="summary-card"><div class="k">路線距離</div><div class="v" id="sum-dist">-</div></div>
          <div class="summary-card"><div class="k">交通模式</div><div class="v" id="sum-mode">-</div></div>
          <div class="summary-card"><div class="k">接駁段</div><div class="v" id="sum-link">-</div></div>
        </div>
        <ul id="steps-list" class="steps-list"></ul>
      </div>
    </div>
  </div>
  <div id="map"></div>
  <div id="status-line"><span id="status-text">請輸入起終點，或在地圖右鍵設定。</span></div>
</div>
<div id="loading"><span>路線計算中...</span></div>


<script>

var map = new Easymap("map");
map.switchMap("osm");
var STATE = {
  mode: 'moto',
  picking: null,
  startXY: null,
  endXY: null,
  passXY: [],
  startMeta: null,
  endMeta: null,
  passMeta: [],
  markerA: null,
  markerB: null,
  passMarkers: [],
  routeObj: null,
  linkObj: null,
  moveMarker: null,
  routePoints: [],
  playPoints: [],
  playTimer: null,
  playStartedAt: 0,
  playElapsed: 0,
  playDuration: 22000,
  lastVoiceKey: '',
  checkpoints: [],
  activeCheckpoint: -1,
  gpsPos: null,       // { x: lon, y: lat }
  gpsEnabled: false,
  gpsMarker: null,
  gpsWatchId: null,
  rightClickMarker: null,
  poiMarker: null
};
var MODE_COLOR = { car:'rgba(37,99,235,0.92)', moto:'rgba(234,88,12,0.92)', walk:'rgba(22,163,74,0.92)' };
var MODE_LABEL = { car:'汽車', moto:'機車', walk:'步行' };
var ADDRESS_API_URL = window.ROUTING_CONFIG.addressApiUrl;
var GEOCODE_LIMIT = 8;
var GEOCODE_TIMERS = {};
var GEOCODE_REQUESTS = {};
var GEOCODE_SUGGESTIONS = {};
var _rcPixel = [0, 0];

function resizeMapSafe() {
  try { map.resize(); } catch(e) {}
}
setTimeout(resizeMapSafe, 80);
setTimeout(resizeMapSafe, 350);
$(window).on('resize', resizeMapSafe);

document.getElementById('map').addEventListener('contextmenu', function(e) {
  _rcPixel = [e.offsetX, e.offsetY];
}, true);
document.getElementById('map').addEventListener('click', function(e) {
  if (!STATE.picking) return;
  var xy = map.revXY(e.offsetX, e.offsetY);
  setPoint(STATE.picking, xy);
  STATE.picking = null;
  setStatus('已設定座標，可繼續設定其他點或開始規劃。');
}, true);

map.addMenu(new dgMenuFunc('設為起點', function() {
  this.parentNode.style.visibility = 'hidden';
  setPoint('start', map.revXY(_rcPixel[0], _rcPixel[1]));
}));
map.addMenu(new dgMenuFunc('設為終點', function() {
  this.parentNode.style.visibility = 'hidden';
  setPoint('end', map.revXY(_rcPixel[0], _rcPixel[1]));
}));
map.addMenu(new dgMenuFunc('新增途徑點', function() {
  this.parentNode.style.visibility = 'hidden';
  addVia(map.revXY(_rcPixel[0], _rcPixel[1]));
}));
map.addMenu(new dgMenuFunc('清除路線', function() {
  this.parentNode.style.visibility = 'hidden';
  clearAll();
}));
map.addMenu(new dgMenuFunc('複製座標', function() {
  this.parentNode.style.visibility = 'hidden';
  var xy = map.revXY(_rcPixel[0], _rcPixel[1]);
  var txt = xy.x.toFixed(6) + ',' + xy.y.toFixed(6);
  navigator.clipboard.writeText(txt).then(function() {
    try {
      smallComment("座標 " + txt + " 已複製", 3, false, {});
    } catch(e) {
      alert("座標已複製: " + txt);
    }
  });
}));
map.addMenu(new dgMenuFunc('這裡有什麼', function() {
  this.parentNode.style.visibility = 'hidden';
  var xy = map.revXY(_rcPixel[0], _rcPixel[1]);
  searchNearestPoi(xy);
}));

$('.mode-btn').on('click', function(){
  applyTravelMode($(this).data('mode'));
  if (STATE.startXY && STATE.endXY) calcRoute({ syncUrl:true });
});
$('[data-pick]').on('click', function(){
  STATE.picking = $(this).data('pick');
  setStatus('請在地圖上點一下設定' + pickLabel(STATE.picking));
});
$('#btn-add-via').on('click', function(){ addVia(null); });
$('#btn-route').on('click', function(){ calcRoute({ syncUrl:true }); });
$('#btn-clear').on('click', clearAll);
$('#btn-play').on('click', playRoute);
$('#btn-pause').on('click', pauseRoute);
$('#btn-stop').on('click', stopRoute);
$('#chk-avoid-hw,#chk-avoid-toll').on('change', function(){
  if (STATE.startXY && STATE.endXY) calcRoute({ syncUrl:true });
});
bindGeocodeInput('start');
bindGeocodeInput('end');

/* ── GPS Functions & Proximity Center ────────────────── */
function gpsIconHtml() {
  return '<span style="position:absolute;left:-18px;top:-18px;display:block;">'
       + '<div style="position:relative;display:flex;align-items:center;justify-content:center;width:36px;height:36px;">'
       + '<div class="my-location-pulse"></div>'
       + '<div class="my-location-dot"></div>'
       + '</div></span>';
}

function updateGpsMarker(lon, lat) {
  var xy = new dgXY(lon, lat);
  if (STATE.gpsMarker) {
    try {
      STATE.gpsMarker.setXY(xy);
    } catch(e) {
      map.removeItem(STATE.gpsMarker);
      STATE.gpsMarker = new dgMarker(xy, gpsIconHtml(), false);
      STATE.gpsMarker.setContent('您的位置<br><small>' + lon.toFixed(6) + ', ' + lat.toFixed(6) + '</small>');
      map.addItem(STATE.gpsMarker);
    }
  } else {
    STATE.gpsMarker = new dgMarker(xy, gpsIconHtml(), false);
    STATE.gpsMarker.setContent('您的位置<br><small>' + lon.toFixed(6) + ', ' + lat.toFixed(6) + '</small>');
    map.addItem(STATE.gpsMarker);
  }
}

function autoInitGps() {
  if (!navigator.geolocation) return;
  navigator.geolocation.getCurrentPosition(function(pos) {
    var lon = pos.coords.longitude;
    var lat = pos.coords.latitude;
    STATE.gpsPos = { x: lon, y: lat };
    STATE.gpsEnabled = true;
    updateGpsMarker(lon, lat);

    // Zoom and pan to GPS location upon loading
    try {
      map.panToXYZ(new dgXY(lon, lat), 15);
    } catch(e) {}

    if (STATE.gpsWatchId !== null) navigator.geolocation.clearWatch(STATE.gpsWatchId);
    STATE.gpsWatchId = navigator.geolocation.watchPosition(function(p) {
      var wLon = p.coords.longitude;
      var wLat = p.coords.latitude;
      STATE.gpsPos = { x: wLon, y: wLat };
      updateGpsMarker(wLon, wLat);
    }, null, { enableHighAccuracy: true, maximumAge: 10000, timeout: 30000 });
  }, function(err) {
    STATE.gpsEnabled = false;
    STATE.gpsPos = null;
  }, { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 });
}

function getSearchCenter() {
  if (STATE.gpsEnabled && STATE.gpsPos) {
    return STATE.gpsPos;
  }
  if (typeof map !== 'undefined' && typeof map.getCenter === 'function') {
    var c = map.getCenter();
    if (c && isFinite(c.x) && isFinite(c.y)) {
      return { x: c.x, y: c.y };
    }
  }
  return null;
}

function rightClickMarkerHtml() {
  return '<span style="position:absolute;left:-7px;top:-7px;width:14px;height:14px;background:#f4e8ff;border:2px solid #7a2cc2;border-radius:50%;box-shadow:0 0 4px rgba(122,44,194,0.6);display:block;"></span>';
}

function searchNearestPoi(xy) {
  if (STATE.rightClickMarker) {
    map.removeItem(STATE.rightClickMarker);
  }
  STATE.rightClickMarker = new dgMarker(xy, rightClickMarkerHtml(), false);
  map.addItem(STATE.rightClickMarker);

  var categories = [
    { id: 'convenience', name: '超商', icon: '🏪', q: '便利商店' },
    { id: 'fuel',        name: '加油', icon: '⛽', q: '加油站' },
    { id: 'toilets',     name: '廁所', icon: '🚻', q: '廁所' },
    { id: 'hardware',    name: '五金', icon: '🛠️', q: '五金' }
  ];

  var popupHtml = '<div class="premium-popup" style="width:320px; min-height:240px;">'
                + '<div class="popup-title">📍 附近 POI 推薦</div>'
                + '<div class="popup-tabs">';

  categories.forEach(function(cat, idx) {
    popupHtml += '<div class="popup-tab' + (idx === 0 ? ' active' : '') + '" data-tab="' + cat.id + '" style="user-select:none;">' + cat.icon + cat.name + '</div>';
  });
  popupHtml += '</div>';

  categories.forEach(function(cat, idx) {
    popupHtml += '<div class="popup-panel' + (idx === 0 ? ' active' : '') + '" id="panel-' + cat.id + '">'
               + '<div class="poi-loading">讀取中...</div>'
               + '</div>';
  });
  popupHtml += '</div>';

  // Open Easymap native Info Window with spacious width and height
  map.openInfoWindow(xy, popupHtml, 360, 340);

  // Parallel asynchronous POI fetching
  categories.forEach(function(cat) {
    var params = 'mode=searchPoi&q=' + encodeURIComponent(cat.q) + '&lon=' + xy.x + '&lat=' + xy.y + '&limit=5';
    myAjax_async_json(ADDRESS_API_URL, params, function(resp) {
      var items = resp && resp.items ? resp.items : [];
      var $panel = $('#panel-' + cat.id);
      if (items.length === 0) {
        $panel.html('<div class="poi-loading">附近無相關地標</div>');
        return;
      }
      var panelHtml = items.map(function(item) {
        var name = item.name || '';
        var dist = Math.round(Number(item.distance_m || 0));
        var distText = dist < 1000 ? dist + 'm' : (dist / 1000).toFixed(1) + 'km';
        var addr = $.trim((item.city || '') + (item.town || '') + (item.road || '') + (item.number || ''));
        if (!addr) addr = 'OSM 地標';
        return '<div class="poi-item" data-lon="' + item.lon + '" data-lat="' + item.lat + '" data-name="' + esc(name) + '">'
             + '<div>'
             + '<div class="poi-name">' + esc(name) + '</div>'
             + '<div class="poi-addr">' + esc(addr) + '</div>'
             + '</div>'
             + '<span class="poi-dist">' + distText + '</span>'
             + '</div>';
      }).join('');
      $panel.html(panelHtml);
    });
  });
}

function applyTravelMode(mode) {
  if ($.inArray(mode, ['car', 'moto', 'walk']) < 0) mode = 'moto';
  STATE.mode = mode;
  $('.mode-btn').removeClass('active');
  $('.mode-btn[data-mode="' + mode + '"]').addClass('active');
}
function pickLabel(key) {
  if (key === 'start') return '起點';
  if (key === 'end') return '終點';
  return '途徑點';
}
function setStatus(txt) { $('#status-text').text(txt); }
function xyText(xy) { return xy.x.toFixed(6) + ',' + xy.y.toFixed(6); }
function parseXY(txt) {
  var m = $.trim(txt || '').match(/^(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)$/);
  if (!m) return null;
  var x = parseFloat(m[1]), y = parseFloat(m[2]);
  if (isNaN(x) || isNaN(y)) return null;
  return new dgXY(x, y);
}
function isCoordinateInput(txt) {
  return parseXY(txt) !== null;
}
function getInputForKey(key) {
  if (key === 'start') return $('#inp-start');
  if (key === 'end') return $('#inp-end');
  if (key.indexOf('via-') === 0) return $('#via-' + viaIndexFromKey(key));
  return $();
}
function getSuggestBoxForKey(key) {
  if (key === 'start') return $('#suggest-start');
  if (key === 'end') return $('#suggest-end');
  if (key.indexOf('via-') === 0) return $('#suggest-via-' + viaIndexFromKey(key));
  return $();
}
function viaIndexFromKey(key) {
  return parseInt(String(key).replace('via-', ''), 10);
}
function getPointMeta(key) {
  if (key === 'start') return STATE.startMeta;
  if (key === 'end') return STATE.endMeta;
  if (key.indexOf('via-') === 0) return STATE.passMeta[viaIndexFromKey(key)] || null;
  return null;
}
function getPointXY(key) {
  if (key === 'start') return STATE.startXY;
  if (key === 'end') return STATE.endXY;
  if (key.indexOf('via-') === 0) return STATE.passXY[viaIndexFromKey(key)] || null;
  return null;
}
function setPointMeta(key, meta) {
  if (key === 'start') STATE.startMeta = meta;
  else if (key === 'end') STATE.endMeta = meta;
  else if (key.indexOf('via-') === 0) STATE.passMeta[viaIndexFromKey(key)] = meta;
}
function setPointXYOnly(key, xy) {
  if (key === 'start') STATE.startXY = xy;
  else if (key === 'end') STATE.endXY = xy;
  else if (key.indexOf('via-') === 0) STATE.passXY[viaIndexFromKey(key)] = xy;
}
function inputMatchesResolvedPoint(key, text) {
  var meta = getPointMeta(key);
  var xy = getPointXY(key);
  text = $.trim(text || '');
  if (!xy || text === '') return false;
  if (meta && $.trim(meta.label || '') === text) return true;
  return xyText(xy) === text;
}
function parseUrlBool(val) {
  return String(val || '') === '1';
}
function makeCoordinateMeta(xy, label) {
  return {
    label: label || xyText(xy),
    subtitle: '',
    source: 'coordinate',
    quality_flag: 'coordinate',
    resolved_by: 'coordinate'
  };
}
function makeUrlMeta(xy, label) {
  if (label) {
    return {
      label: label,
      subtitle: '',
      source: '',
      quality_flag: '',
      resolved_by: 'url_label'
    };
  }
  var meta = makeCoordinateMeta(xy, xyText(xy));
  meta.resolved_by = 'url_coordinate';
  return meta;
}
function metaDisplayLabel(meta, xy) {
  if (meta && $.trim(meta.label || '') !== '') return $.trim(meta.label);
  return xy ? xyText(xy) : '';
}
function labelForUrl(meta, xy) {
  var label = metaDisplayLabel(meta, xy);
  return label && xy && label !== xyText(xy) ? label : '';
}
function placePointFromState(key) {
  var xy = getPointXY(key);
  if (!xy) return;
  if (key === 'start') placeMarker('A', xy);
  else if (key === 'end') placeMarker('B', xy);
  else renderViaMarkers();
}
function loadRouteFromUrl() {
  var q = new URLSearchParams(window.location.search);
  var hasUrlRoute = q.has('start') && q.has('end');
  if (!hasUrlRoute) {
    applyTravelMode('moto');
    $('#chk-avoid-hw,#chk-avoid-toll').prop('checked', false);
    return false;
  }

  var start = parseXY(q.get('start'));
  var end = parseXY(q.get('end'));
  if (!start || !end) {
    applyTravelMode('moto');
    $('#chk-avoid-hw,#chk-avoid-toll').prop('checked', false);
    return false;
  }

  var startLabel = $.trim(q.get('start_label') || '');
  var endLabel = $.trim(q.get('end_label') || '');
  $('#inp-start').val(startLabel || xyText(start));
  $('#inp-end').val(endLabel || xyText(end));
  STATE.startXY = start;
  STATE.endXY = end;
  STATE.startMeta = makeUrlMeta(start, startLabel);
  STATE.endMeta = makeUrlMeta(end, endLabel);
  applyTravelMode(q.get('mode') || 'moto');
  $('#chk-avoid-hw').prop('checked', parseUrlBool(q.get('avoid_highway')));
  $('#chk-avoid-toll').prop('checked', parseUrlBool(q.get('avoid_toll')));

  STATE.passXY = [];
  STATE.passMeta = [];
  var via = $.trim(q.get('via') || '');
  var viaLabels = ($.trim(q.get('via_label') || '')).split('|');
  if (via !== '') {
    $.each(via.split('|'), function(i, item) {
      var xy = parseXY(item);
      if (xy) {
        STATE.passXY.push(xy);
        STATE.passMeta.push(makeUrlMeta(xy, $.trim(viaLabels[i] || '')));
      }
    });
  }
  renderViaList();
  placeMarker('A', start);
  placeMarker('B', end);
  renderViaMarkers();
  setPointQualityText('start', STATE.startMeta);
  setPointQualityText('end', STATE.endMeta);
  return true;
}
function buildRouteShareUrl() {
  var q = new URLSearchParams();
  q.set('start', STATE.startXY.x + ',' + STATE.startXY.y);
  q.set('end', STATE.endXY.x + ',' + STATE.endXY.y);
  var startLabel = labelForUrl(STATE.startMeta, STATE.startXY);
  var endLabel = labelForUrl(STATE.endMeta, STATE.endXY);
  if (startLabel) q.set('start_label', startLabel);
  if (endLabel) q.set('end_label', endLabel);
  q.set('mode', STATE.mode);
  q.set('avoid_highway', $('#chk-avoid-hw').is(':checked') ? '1' : '0');
  q.set('avoid_toll', $('#chk-avoid-toll').is(':checked') ? '1' : '0');
  var via = STATE.passXY.map(function(p){ return p ? p.x + ',' + p.y : ''; }).filter(function(v){ return v !== ''; });
  if (via.length) q.set('via', via.join('|'));
  var viaLabels = [];
  $.each(STATE.passXY, function(i, xy) {
    if (!xy) return;
    viaLabels.push(labelForUrl(STATE.passMeta[i], xy));
  });
  if (viaLabels.join('').length) q.set('via_label', viaLabels.join('|'));
  return window.location.pathname + '?' + q.toString();
}
function syncRouteUrl() {
  if (!STATE.startXY || !STATE.endXY || !window.history || !window.history.replaceState) return;
  window.history.replaceState(null, '', buildRouteShareUrl());
}
function qualityLabel(meta) {
  if (!meta) return '';
  if (meta.resolved_by === 'map_pick') return '地圖選點';
  if (meta.source === 'coordinate' || meta.quality_flag === 'coordinate') return '座標';
  if (meta.source === 'landmark_alias' || meta.quality_flag === 'official_alias') return '地標別名';
  if (meta.source === 'osm_poi' || meta.quality_flag === 'osm_reference') return 'OSM 地標';
  if (meta.quality_flag === 'approximate' || meta.source === 'osm_road_center') return '道路約略位置';
  if (meta.quality_flag === 'ok' || meta.source === 'official') return '官方門牌';
  return meta.source || meta.quality_flag || '';
}
function setPointQualityText(key, meta) {
  var text = qualityLabel(meta);
  if (key === 'start') $('#start-snap').text(text);
  else if (key === 'end') $('#end-snap').text(text);
  else if (key.indexOf('via-') === 0) $('#via-meta-' + viaIndexFromKey(key)).text(text);
}
function setPointError(key, message) {
  getInputForKey(key).addClass('geo-error');
  if (key === 'start') $('#start-snap').text(message);
  else if (key === 'end') $('#end-snap').text(message);
  else if (key.indexOf('via-') === 0) $('#via-meta-' + viaIndexFromKey(key)).text(message);
}
function clearPointError(key) {
  getInputForKey(key).removeClass('geo-error');
}
function bindGeocodeInput(key) {
  getInputForKey(key).on('input', function(){
    clearPointError(key);
    clearRouteDisplay();
    scheduleGeocodeSuggestions(key);
  });
  getInputForKey(key).on('focus', function(){
    scheduleGeocodeSuggestions(key);
  });
  getInputForKey(key).on('blur', function(){
    setTimeout(function(){ hideGeocodeSuggestions(key); }, 180);
  });
}
function scheduleGeocodeSuggestions(key) {
  var text = $.trim(getInputForKey(key).val() || '');
  if (GEOCODE_TIMERS[key]) clearTimeout(GEOCODE_TIMERS[key]);
  if (text.length < 2 || isCoordinateInput(text) || inputMatchesResolvedPoint(key, text)) {
    hideGeocodeSuggestions(key);
    return;
  }
  GEOCODE_TIMERS[key] = setTimeout(function(){
    fetchGeocodeSuggestions(key, text);
  }, 260);
}
function fetchGeocodeSuggestions(key, query) {
  var requestId = (GEOCODE_REQUESTS[key] || 0) + 1;
  GEOCODE_REQUESTS[key] = requestId;
  var params = 'mode=autocomplete&q=' + encodeURIComponent(query) + '&limit=' + GEOCODE_LIMIT;
  var center = getSearchCenter();
  if (center) {
    params += '&lon=' + center.x + '&lat=' + center.y;
  }
  myAjax_async_json(ADDRESS_API_URL, params, function(data) {
    if (GEOCODE_REQUESTS[key] !== requestId) return;
    var items = normalizeGeocodeItems(data && data.items ? data.items : []);
    GEOCODE_SUGGESTIONS[key] = { query:query, items:items };
    renderGeocodeSuggestions(key, query, items);
  });
}
function normalizeGeocodeItems(items) {
  var out = [];
  $.each(items || [], function(i, item) {
    var resolved = normalizeGeocodeItem(item, '');
    if (resolved) out.push(resolved.item);
  });
  return out;
}
function normalizeGeocodeItem(item, rawQuery) {
  if (!item) return null;
  var lon = parseFloat(item.lon);
  var lat = parseFloat(item.lat);
  if (isNaN(lon) || isNaN(lat)) return null;
  var title = $.trim(item.title || item.full_addr || item.address || rawQuery || '');
  var subtitle = $.trim(item.subtitle || item.full_addr || '');
  var source = $.trim(item.source || item.db_source || item.geocode_source || '');
  var quality = $.trim(item.quality_flag || '');
  return {
    xy: new dgXY(lon, lat),
    item: item,
    meta: {
      label: title || (lon + ',' + lat),
      subtitle: subtitle,
      source: source,
      quality_flag: quality,
      resolved_by: 'autocomplete',
      raw_query: rawQuery || ''
    }
  };
}
function renderGeocodeSuggestions(key, query, items) {
  var box = getSuggestBoxForKey(key);
  if (!items.length) {
    box.html('<div class="geo-option"><span class="geo-title">找不到符合的地址或地標</span><span class="geo-subtitle">請換個關鍵字，或直接輸入 lon,lat</span></div>').show();
    return;
  }
  var html = '';
  $.each(items, function(i, item) {
    var resolved = normalizeGeocodeItem(item, query);
    if (!resolved) return;
    var distText = '';
    if (item.distance_m !== undefined && item.distance_m !== null) {
      var dist = Math.round(Number(item.distance_m));
      var label = dist < 1000 ? dist + 'm' : (dist / 1000).toFixed(1) + 'km';
      distText = '<span style="float:right; font-size:11px; font-weight:700; color:#60a5fa; background:rgba(96,165,250,0.1); padding:2px 6px; border-radius:9999px; margin-top:2px;">📍 ' + label + '</span>';
    }
    html += '<button type="button" class="geo-option" data-idx="' + i + '" style="position:relative; overflow:hidden;">' +
      distText +
      '<span class="geo-title">' + esc(resolved.meta.label) + '</span>' +
      '<span class="geo-subtitle">' + esc(resolved.meta.subtitle || (item.city || '') + (item.town || '')) + '</span>' +
      '<span class="geo-source">' + esc(qualityLabel(resolved.meta)) + '</span>' +
    '</button>';
  });
  box.html(html).show();
  box.find('.geo-option').on('click', function(){
    var idx = parseInt($(this).data('idx'), 10);
    var item = (GEOCODE_SUGGESTIONS[key] && GEOCODE_SUGGESTIONS[key].items) ? GEOCODE_SUGGESTIONS[key].items[idx] : null;
    if (item) applyResolvedPoint(key, item, query);
  });
}
function hideGeocodeSuggestions(key) {
  getSuggestBoxForKey(key).hide();
}
function applyResolvedPoint(key, item, rawQuery) {
  var resolved = normalizeGeocodeItem(item, rawQuery);
  if (!resolved) return false;
  clearRouteDisplay();
  setPointXYOnly(key, resolved.xy);
  setPointMeta(key, resolved.meta);
  getInputForKey(key).val(resolved.meta.label);
  clearPointError(key);
  setPointQualityText(key, resolved.meta);
  hideGeocodeSuggestions(key);
  placePointFromState(key);
  return true;
}
function geocodeFirstCandidate(key, query, done) {
  var params = 'mode=autocomplete&q=' + encodeURIComponent(query) + '&limit=1';
  var center = getSearchCenter();
  if (center) {
    params += '&lon=' + center.x + '&lat=' + center.y;
  }
  myAjax_async_json(ADDRESS_API_URL, params, function(data) {
    var items = normalizeGeocodeItems(data && data.items ? data.items : []);
    if (!items.length) {
      setPointError(key, '查無結果');
      done(false, pickLabel(key) + '找不到符合的地址或地標');
      return;
    }
    applyResolvedPoint(key, items[0], query);
    done(true);
  });
}
function resolvePointInput(key, required, done) {
  var input = getInputForKey(key);
  var text = $.trim(input.val() || '');
  clearPointError(key);
  if (text === '') {
    if (required) {
      setPointError(key, '必填');
      done(false, '請輸入' + pickLabel(key));
    } else {
      done(true);
    }
    return;
  }
  var xy = parseXY(text);
  if (xy) {
    setPoint(key, xy);
    done(true);
    return;
  }
  if (inputMatchesResolvedPoint(key, text)) {
    done(true);
    return;
  }
  if (text.length < 2) {
    setPointError(key, '至少2字');
    done(false, pickLabel(key) + '請輸入至少 2 個字，或使用 lon,lat');
    return;
  }
  geocodeFirstCandidate(key, text, done);
}
function resolveRouteInputs(done) {
  var keys = ['start'];
  $('.via-input').each(function(){
    var id = $(this).attr('id') || '';
    if (id.indexOf('via-') === 0 && $.trim($(this).val() || '') !== '') keys.push(id);
  });
  keys.push('end');
  var i = 0;
  function next() {
    if (i >= keys.length) {
      done(true);
      return;
    }
    var key = keys[i++];
    resolvePointInput(key, key === 'start' || key === 'end', function(ok, message) {
      if (!ok) {
        done(false, message || '地址解析失敗');
        return;
      }
      next();
    });
  }
  next();
}
function setPoint(key, xy) {
  var meta = makeCoordinateMeta(xy);
  if (key === 'start') {
    STATE.startXY = xy;
    STATE.startMeta = meta;
    $('#inp-start').val(xyText(xy));
    placeMarker('A', xy);
  } else if (key === 'end') {
    STATE.endXY = xy;
    STATE.endMeta = meta;
    $('#inp-end').val(xyText(xy));
    placeMarker('B', xy);
  } else if (key.indexOf('via-') === 0) {
    var idx = parseInt(key.replace('via-', ''), 10);
    STATE.passXY[idx] = xy;
    STATE.passMeta[idx] = meta;
    $('#via-' + idx).val(xyText(xy));
    renderViaMarkers();
  }
  clearRouteDisplay();
  setPointQualityText(key, meta);
}
function readInputsToState() {
  var s = parseXY($('#inp-start').val());
  var e = parseXY($('#inp-end').val());
  if (s) { STATE.startXY = s; STATE.startMeta = makeCoordinateMeta(s); placeMarker('A', s); setPointQualityText('start', STATE.startMeta); }
  if (e) { STATE.endXY = e; STATE.endMeta = makeCoordinateMeta(e); placeMarker('B', e); setPointQualityText('end', STATE.endMeta); }
  STATE.passXY = [];
  STATE.passMeta = [];
  $('.via-input').each(function(){
    var xy = parseXY($(this).val());
    if (xy) {
      STATE.passXY.push(xy);
      STATE.passMeta.push(makeCoordinateMeta(xy));
    }
  });
  renderViaList();
  renderViaMarkers();
}
function addVia(xy) {
  STATE.passXY.push(xy);
  STATE.passMeta.push(xy ? makeCoordinateMeta(xy) : null);
  renderViaList();
  if (xy) renderViaMarkers();
}
function renderViaList() {
  var html = '';
  $.each(STATE.passXY, function(i, xy){
    var meta = STATE.passMeta[i] || null;
    html += '<div class="field-row via-row" data-idx="' + i + '">' +
      '<div class="field-label"><span>途徑點 ' + (i + 1) + '</span><span id="via-meta-' + i + '" class="point-quality">' + esc(qualityLabel(meta)) + '</span></div>' +
      '<div class="via-item">' +
        '<input id="via-' + i + '" class="via-input" type="text" value="' + esc(metaDisplayLabel(meta, xy)) + '" placeholder="地址、地標或 lon,lat" autocomplete="off">' +
        '<button class="mini-btn via-pick" type="button" data-idx="' + i + '">選點</button>' +
        '<button class="mini-btn danger-btn via-del" type="button" data-idx="' + i + '">刪</button>' +
      '</div>' +
      '<div id="suggest-via-' + i + '" class="geo-suggest"></div>' +
    '</div>';
  });
  $('#via-list').html(html);
  $('.via-input').each(function(){
    var id = $(this).attr('id') || '';
    if (id.indexOf('via-') === 0) bindGeocodeInput(id);
  });
  $('.via-pick').on('click', function(){
    STATE.picking = 'via-' + $(this).data('idx');
    setStatus('請在地圖上點一下設定途徑點');
  });
  $('.via-del').on('click', function(){
    var idx = parseInt($(this).data('idx'), 10);
    STATE.passXY.splice(idx, 1);
    STATE.passMeta.splice(idx, 1);
    renderViaList();
    renderViaMarkers();
    clearRouteDisplay();
  });
}
function renderViaMarkers() {
  $.each(STATE.passMarkers, function(i, m){ map.removeItem(m); });
  STATE.passMarkers = [];
  $.each(STATE.passXY, function(i, xy){
    if (!xy) return;
    var m = new dgMarker(xy, circleMarkerHtml(i + 1), false);
    map.addItem(m);
    STATE.passMarkers.push(m);
  });
}
function markerHtml(label, color) {
  var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="40">' +
    '<path d="M16 0C7.2 0 0 7.2 0 16c0 12 16 24 16 24s16-12 16-24C32 7.2 24.8 0 16 0z" fill="' + color + '"/>' +
    '<text x="16" y="21" text-anchor="middle" fill="white" font-size="14" font-weight="bold">' + label + '</text></svg>';
  return '<span style="position:absolute;left:-16px;top:-40px;display:block;"><img src="data:image/svg+xml;utf8,' + encodeURIComponent(svg) + '" width="32" height="40"></span>';
}
function circleMarkerHtml(label) {
  var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24">' +
    '<circle cx="12" cy="12" r="11" fill="#2563eb" stroke="white" stroke-width="2"/>' +
    '<text x="12" y="17" text-anchor="middle" fill="white" font-size="12" font-weight="bold">' + label + '</text></svg>';
  return '<span style="position:absolute;left:-12px;top:-12px;display:block;"><img src="data:image/svg+xml;utf8,' + encodeURIComponent(svg) + '" width="24" height="24"></span>';
}
function movingMarkerHtml() {
  return '<span style="position:absolute;left:-10px;top:-10px;width:20px;height:20px;background:#111827;border:3px solid #fff;border-radius:50%;box-shadow:0 1px 6px rgba(0,0,0,.45);display:block;"></span>';
}
function placeMarker(label, xy) {
  var key = label === 'A' ? 'markerA' : 'markerB';
  if (STATE[key]) map.removeItem(STATE[key]);
  STATE[key] = new dgMarker(xy, markerHtml(label, label === 'A' ? '#16a34a' : '#dc2626'), false);
  map.addItem(STATE[key]);
}

function calcRoute(options) {
  options = $.extend({ syncUrl:true }, options || {});
  $('#loading span').text('地址解析中...');
  $('#loading').addClass('show');
  resolveRouteInputs(function(ok, message) {
    if (!ok) {
      $('#loading').removeClass('show');
      dialogMyBoxOn('地址查詢失敗', message || '地址或地標無法轉成經緯度', 340, 150);
      setStatus(message || '地址解析失敗，請修正後再試。');
      return;
    }
    calcRouteResolved(options);
  });
}

function calcRouteResolved(options) {
  if (!STATE.startXY || !STATE.endXY) {
    $('#loading').removeClass('show');
    dialogMyBoxOn('資料不足', '請先設定起點與終點', 300, 130);
    return;
  }
  stopRoute();
  clearRouteDisplay();
  $('#loading span').text('路線計算中...');
  var passStr = STATE.passXY.map(function(p){ return p ? p.x + ',' + p.y : ''; }).filter(function(v){ return v !== ''; }).join('\n');
  var params = 'mode=routing_path' +
    '&start_point=' + encodeURIComponent(STATE.startXY.x + ',' + STATE.startXY.y) +
    '&end_point=' + encodeURIComponent(STATE.endXY.x + ',' + STATE.endXY.y) +
    '&travel_mode=' + encodeURIComponent(STATE.mode) +
    '&avoid_highway=' + ($('#chk-avoid-hw').is(':checked') ? 1 : 0) +
    '&avoid_toll=' + ($('#chk-avoid-toll').is(':checked') ? 1 : 0) +
    (passStr ? '&passpath=' + encodeURIComponent(passStr) : '');

  myAjax_async_json('api.php', params, function(data) {
    $('#loading').removeClass('show');
    if (!data || data.status !== 'OK') {
      dialogMyBoxOn('路線規劃失敗', (data && data.msg) ? data.msg : '查無路徑，請換個起終點再試', 320, 150);
      return;
    }
    showRoute(data, options);
  });
}

function showRoute(data, options) {
  options = $.extend({ syncUrl:true }, options || {});
  var parts = extractRouteParts(data);
  if (parts.length === 0) {
    dialogMyBoxOn('路線規劃失敗', 'API 未回傳可繪製的路線', 320, 150);
    return;
  }

  var routeWkts = [];
  var routePoints = [];
  var color = MODE_COLOR[STATE.mode] || MODE_COLOR.car;
  $.each(parts, function(i, part) {
    var pts = parseWktPoints(part.wkt);
    if (pts.length === 0) return;
    routeWkts.push({ wkt: part.wkt, label: '', style_setting: {
      "LineString": { "width": 6, "stroke-color": color, "linedash": [0], "linecap": "round" },
      "MultiLineString": { "width": 6, "stroke-color": color, "linedash": [0], "linecap": "round" }
    }});
    routePoints = routePoints.concat(pts);
  });

  STATE.routeObj = new dgWKT(routeWkts, 'EPSG:4326');
  map.addItem(STATE.routeObj);

  var links = buildLinkWkts(parts);
  if (links.wkts.length > 0) {
    STATE.linkObj = new dgWKT(links.wkts, 'EPSG:4326');
    map.addItem(STATE.linkObj);
  }

  STATE.routePoints = routePoints;
  STATE.playPoints = buildPlayPoints(parts, links.segments);
  STATE.playDuration = Math.max(12000, Math.min(45000, STATE.playPoints.length * 70));

  try { map.zoomToExtent(map.getDGSExtent([STATE.routeObj], 'EPSG:4326')); } catch(e) {}
  updateSummary(data, links.totalLength);
  if (options.syncUrl) syncRouteUrl();
  setStatus('路線已完成，可按播放模擬移動。');
  speak('路線規劃完成，預估 ' + (data.total_cost_min || data.summary.total_cost_min) + ' 分鐘。', 'ready');
}
function extractRouteParts(data) {
  if (data.route_parts && data.route_parts.length) return data.route_parts;
  var parts = [];
  $.each(data.data || {}, function(seg, rows) {
    $.each(rows, function(i, row) {
      if (row.wkt) parts.push({ road_num: parseInt(seg, 10), wkt: row.wkt, cost_sec: parseFloat(row.Cost || 0) });
    });
  });
  return parts.sort(function(a, b){ return a.road_num - b.road_num; });
}
function parseWktPoints(wkt) {
  var pts = [];
  var re = /(-?\d+(?:\.\d+)?)\s+(-?\d+(?:\.\d+)?)/g;
  var m;
  while ((m = re.exec(wkt)) !== null) pts.push({ x: parseFloat(m[1]), y: parseFloat(m[2]) });
  return pts;
}
function lineWkt(a, b) { return 'LINESTRING(' + a.x + ' ' + a.y + ',' + b.x + ' ' + b.y + ')'; }
function distanceM(a, b) {
  var r = 6371000, p1 = a.y * Math.PI / 180, p2 = b.y * Math.PI / 180;
  var dp = (b.y - a.y) * Math.PI / 180, dl = (b.x - a.x) * Math.PI / 180;
  var x = Math.sin(dp / 2) * Math.sin(dp / 2) + Math.cos(p1) * Math.cos(p2) * Math.sin(dl / 2) * Math.sin(dl / 2);
  return r * 2 * Math.atan2(Math.sqrt(x), Math.sqrt(1 - x));
}
function linkStyle() {
  return {
    "LineString": { "width": 4, "stroke-color": "rgba(71,85,105,0.85)", "linedash": [2, 9], "linecap": "round" },
    "MultiLineString": { "width": 4, "stroke-color": "rgba(71,85,105,0.85)", "linedash": [2, 9], "linecap": "round" }
  };
}
function addLink(out, a, b, label) {
  if (!a || !b) return;
  var d = distanceM(a, b);
  if (d < 3) return;
  var seg = { from:a, to:b, label:label, length:d };
  out.segments.push(seg);
  out.totalLength += d;
  out.wkts.push({ wkt:lineWkt(a, b), label:label, style_setting:linkStyle() });
}
function buildLinkWkts(parts) {
  var out = { wkts:[], segments:[], totalLength:0 };
  if (parts.length === 0) return out;
  var firstPts = parseWktPoints(parts[0].wkt);
  var lastPts = parseWktPoints(parts[parts.length - 1].wkt);
  addLink(out, STATE.startXY, firstPts[0], '起點接駁');
  $.each(STATE.passXY, function(i, via) {
    var prev = parseWktPoints(parts[i] ? parts[i].wkt : '');
    var next = parseWktPoints(parts[i + 1] ? parts[i + 1].wkt : '');
    if (prev.length) addLink(out, prev[prev.length - 1], via, '途徑點接駁');
    if (next.length) addLink(out, via, next[0], '途徑點接駁');
  });
  addLink(out, lastPts[lastPts.length - 1], STATE.endXY, '終點接駁');
  return out;
}
function buildPlayPoints(parts, links) {
  var pts = [];
  function pushPoint(p) {
    if (!p) return;
    if (pts.length && distanceM(pts[pts.length - 1], p) < 1) return;
    pts.push({ x:p.x, y:p.y });
  }
  if (links.length && links[0].label === '起點接駁') {
    pushPoint(links[0].from); pushPoint(links[0].to);
  } else {
    pushPoint(STATE.startXY);
  }
  $.each(parts, function(i, part) {
    $.each(parseWktPoints(part.wkt), function(j, p){ pushPoint(p); });
    $.each(links, function(k, link) {
      if (link.label === '途徑點接駁' && distanceM(pts[pts.length - 1], link.from) < 2) {
        pushPoint(link.to);
      }
    });
  });
  $.each(links, function(i, link) {
    if (link.label === '終點接駁') { pushPoint(link.from); pushPoint(link.to); }
  });
  pushPoint(STATE.endXY);
  return pts;
}
function updateSummary(data, linkLength) {
  var summary = data.summary || data;
  $('#sum-time').text((summary.total_cost_min || data.total_cost_min || 0) + ' 分');
  $('#sum-dist').text((summary.total_length_km || data.total_length_km || 0) + ' km');
  $('#sum-mode').text(MODE_LABEL[STATE.mode] + (summary.avoid ? ' 避開' : ''));
  $('#sum-link').text(linkLength > 0 ? Math.round(linkLength) + ' m' : '0 m');
  $('#start-snap').text(pointStatusText('start', data.snap_points && data.snap_points.start));
  $('#end-snap').text(pointStatusText('end', data.snap_points && data.snap_points.end));
  STATE.checkpoints = buildCheckpoints(data.segments || []);
  STATE.activeCheckpoint = -1;
  var html = '';
  $.each(STATE.checkpoints, function(i, s) {
    html += '<li class="route-step" data-step="' + i + '">' +
      '<span class="step-icon">' + (i + 1) + '</span>' +
      '<span class="step-main">' +
        '<span class="step-name">' + esc(s.name || '未命名道路') + '</span>' +
        '<span class="step-meta">約 ' + (s.cost_min || 0) + ' 分</span>' +
      '</span>' +
    '</li>';
  });
  if (html === '') html = '<li class="route-step"><span class="step-icon">-</span><span class="step-main"><span class="step-name">目前 API 未提供逐路名摘要</span></span></li>';
  $('#steps-list').html(html);
  $('#result-box').show();
}
function buildCheckpoints(segments) {
  var list = [];
  $.each(segments.slice(0, 30), function(i, s) {
    list.push({
      name: s.name || '未命名道路',
      cost_min: s.cost_min || 0,
      ratio: segments.length <= 1 ? 1 : i / Math.max(1, Math.min(segments.length, 30) - 1)
    });
  });
  return list;
}
function updateRouteProgress(ratio) {
  if (!STATE.checkpoints.length) return;
  var idx = Math.floor(ratio * STATE.checkpoints.length);
  if (idx >= STATE.checkpoints.length) idx = STATE.checkpoints.length - 1;
  if (idx < 0) idx = 0;
  if (idx === STATE.activeCheckpoint && ratio < 1) return;

  STATE.activeCheckpoint = idx;
  $('#steps-list .route-step').each(function(i) {
    var $li = $(this);
    $li.removeClass('done active');
    if (ratio >= 1 || i < idx) {
      $li.addClass('done');
      $li.find('.step-icon').text('✓');
    } else if (i === idx) {
      $li.addClass('active');
      $li.find('.step-icon').text('▶');
    } else {
      $li.find('.step-icon').text(i + 1);
    }
  });

  var dom = $('#steps-list .route-step').eq(idx)[0];
  if (dom && typeof dom.scrollIntoView === 'function') {
    dom.scrollIntoView({ block:'nearest', behavior:'smooth' });
  }
}
function snapText(s) {
  if (!s || s.distance_m == null) return '';
  return '吸附 ' + Math.round(parseFloat(s.distance_m)) + 'm';
}
function pointStatusText(key, snap) {
  var parts = [];
  var snapLabel = snapText(snap);
  var quality = qualityLabel(getPointMeta(key));
  if (snapLabel) parts.push(snapLabel);
  if (quality) parts.push(quality);
  return parts.join(' · ');
}
function esc(str) { return $('<div>').text(str).html(); }

function playRoute() {
  if (!STATE.playPoints.length) {
    dialogMyBoxOn('尚無路線', '請先完成路線規劃', 300, 130);
    return;
  }
  if (!STATE.moveMarker) {
    STATE.moveMarker = new dgMarker(new dgXY(STATE.playPoints[0].x, STATE.playPoints[0].y), movingMarkerHtml(), false);
    map.addItem(STATE.moveMarker);
  }
  STATE.playStartedAt = Date.now() - STATE.playElapsed;
  updateRouteProgress(Math.min(1, STATE.playElapsed / STATE.playDuration));
  speak('開始模擬移動。', 'start');
  tickPlay();
}
function pauseRoute() {
  if (STATE.playTimer) clearTimeout(STATE.playTimer);
  STATE.playTimer = null;
  setStatus('播放已暫停。');
}
function stopRoute() {
  if (STATE.playTimer) clearTimeout(STATE.playTimer);
  STATE.playTimer = null;
  STATE.playElapsed = 0;
  STATE.lastVoiceKey = '';
  STATE.activeCheckpoint = -1;
  resetRouteProgress();
  if (STATE.moveMarker) {
    map.removeItem(STATE.moveMarker);
    STATE.moveMarker = null;
  }
  if (window.speechSynthesis) window.speechSynthesis.cancel();
}
function tickPlay() {
  if (STATE.playTimer) clearTimeout(STATE.playTimer);
  STATE.playElapsed = Date.now() - STATE.playStartedAt;
  var ratio = Math.min(1, STATE.playElapsed / STATE.playDuration);
  var pos = interpolatePath(STATE.playPoints, ratio);
  movePlaybackMarker(pos);
  updateRouteProgress(ratio);
  if (ratio > 0.45) speak('持續沿路線前進。', 'mid');
  setStatus('播放進度 ' + Math.round(ratio * 100) + '%');
  if (ratio >= 1) {
    speak('已抵達終點。', 'end');
    setStatus('已抵達終點。');
    STATE.playTimer = null;
    return;
  }
  STATE.playTimer = setTimeout(tickPlay, 80);
}
function resetRouteProgress() {
  $('#steps-list .route-step').each(function(i) {
    $(this).removeClass('done active');
    $(this).find('.step-icon').text(i + 1);
  });
}
function interpolatePath(points, ratio) {
  if (points.length <= 1) return points[0];
  var total = 0, lens = [];
  for (var i = 1; i < points.length; i++) {
    var d = distanceM(points[i - 1], points[i]);
    lens.push(d); total += d;
  }
  var target = total * ratio, acc = 0;
  for (var j = 1; j < points.length; j++) {
    var seg = lens[j - 1];
    if (acc + seg >= target) {
      var t = seg === 0 ? 0 : (target - acc) / seg;
      return { x:points[j - 1].x + (points[j].x - points[j - 1].x) * t, y:points[j - 1].y + (points[j].y - points[j - 1].y) * t };
    }
    acc += seg;
  }
  return points[points.length - 1];
}
function movePlaybackMarker(pos) {
  var xy = new dgXY(pos.x, pos.y);
  if (STATE.moveMarker && typeof STATE.moveMarker.setXY === 'function') {
    STATE.moveMarker.setXY(xy);
  } else {
    if (STATE.moveMarker) map.removeItem(STATE.moveMarker);
    STATE.moveMarker = new dgMarker(xy, movingMarkerHtml(), false);
    map.addItem(STATE.moveMarker);
  }
}
function speak(txt, key) {
  if (!$('#chk-voice').is(':checked') || STATE.lastVoiceKey === key) return;
  STATE.lastVoiceKey = key;
  if (!window.speechSynthesis || !window.SpeechSynthesisUtterance) return;
  var u = new SpeechSynthesisUtterance(txt);
  u.lang = 'zh-TW';
  u.rate = 1;
  window.speechSynthesis.speak(u);
}
function clearRouteDisplay() {
  stopRoute();
  if (STATE.routeObj) { map.removeItem(STATE.routeObj); STATE.routeObj = null; }
  if (STATE.linkObj) { map.removeItem(STATE.linkObj); STATE.linkObj = null; }
  STATE.routePoints = [];
  STATE.playPoints = [];
  STATE.checkpoints = [];
  STATE.activeCheckpoint = -1;
  $('#result-box').hide();
  $('#start-snap,#end-snap').text('');
}
function clearAll() {
  clearRouteDisplay();
  if (STATE.markerA) { map.removeItem(STATE.markerA); STATE.markerA = null; }
  if (STATE.markerB) { map.removeItem(STATE.markerB); STATE.markerB = null; }
  if (STATE.rightClickMarker) { map.removeItem(STATE.rightClickMarker); STATE.rightClickMarker = null; }
  if (STATE.poiMarker) { map.removeItem(STATE.poiMarker); STATE.poiMarker = null; }
  $.each(STATE.passMarkers, function(i, m){ map.removeItem(m); });
  STATE.passMarkers = [];
  STATE.passXY = [];
  STATE.passMeta = [];
  STATE.startXY = null;
  STATE.endXY = null;
  STATE.startMeta = null;
  STATE.endMeta = null;
  $('#inp-start,#inp-end').val('');
  renderViaList();
  setStatus('請輸入起終點，或在地圖右鍵設定。');
}

function initDefaultRouteUi() {
  var loadedFromUrl = loadRouteFromUrl();
  if (!loadedFromUrl) readInputsToState();
  try {
    map.panToXYZ(new dgXYZ(120.66593650660198, 24.144814148293747), 13);
  } catch(e) {
    try { map.panToXYZ(new dgXY(120.66593650660198, 24.144814148293747), 13); } catch(e2) {}
  }
  resizeMapSafe();
  setStatus(loadedFromUrl ? '已由網址載入路線並自動規劃。' : '已載入預設路線並自動規劃。');
  try { smallComment(loadedFromUrl ? '已由網址載入路線' : '已載入預設路線', 3000, false, {}); } catch(e) {}
  var arrow = null;
  var circle = null;
  try { arrow = drawArrowTojQDom($('#btn-route')); } catch(e) {}
  try { circle = drawCircleTojQDom($('#btn-route')); } catch(e) {}
  setTimeout(function(){
    try { if (arrow && arrow.destroy) arrow.destroy(); } catch(e) {}
    try { if (circle) circle.remove(); } catch(e) {}
  }, 3000);
  if (STATE.startXY && STATE.endXY) calcRoute({ syncUrl:false });

  // Prompt GPS geolocation automatically upon default UI initialization!
  autoInitGps();
}

// jQuery Document Delegations for Tabbed Recommendations & Click Positioning
$(document).on('click', '.popup-tab', function() {
  var tab = $(this).data('tab');
  $(this).siblings('.popup-tab').removeClass('active');
  $(this).addClass('active');
  var $popup = $(this).closest('.premium-popup');
  $popup.find('.popup-panel').removeClass('active');
  $popup.find('#panel-' + tab).addClass('active');
});

$(document).on('click', '.poi-item', function() {
  var lon = Number($(this).data('lon'));
  var lat = Number($(this).data('lat'));
  var name = $(this).data('name');
  var pt = new dgXY(lon, lat);

  if (STATE.poiMarker) {
    map.removeItem(STATE.poiMarker);
  }
  STATE.poiMarker = new dgMarker(pt, circleMarkerHtml('📍'), false);
  STATE.poiMarker.setContent('<strong>' + esc(name) + '</strong><br>' + lon.toFixed(6) + ', ' + lat.toFixed(6));
  map.addItem(STATE.poiMarker);

  try {
    map.panToXYZ(pt, 17);
  } catch(e) {}
});

setTimeout(initDefaultRouteUi, 450);

</script>
</body>
</html>
