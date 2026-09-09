<?php
  $base_dir = __DIR__;
  require "{$base_dir}/html.php";
  require "{$base_dir}/head.php";
?>
<title>路線規劃 API Tester</title>
<style>
*, *::before, *::after { box-sizing:border-box; }
body { margin:0; font-family:system-ui,sans-serif; background:#0f172a; color:#e5e7eb; }


h2 { color:#f8fafc; margin:20px 0 16px; }
a { color:#60a5fa; }
.menu-box { background:#111827; border:1px solid #334155; border-radius:6px; padding:12px 18px; margin-bottom:18px; }
.menu-box select { background:#1e293b; color:#e5e7eb; border:1px solid #475569; border-radius:4px; padding:4px 8px; font-size:13px; margin-left:6px; }
#menu-list { margin:8px 0 0; padding-left:18px; color:#94a3b8; font-size:13px; }
#menu-list a { color:#60a5fa; }
.api_table { width:100%; border-collapse:collapse; }
.api_table th, .api_table td { border:1px solid #334155; padding:10px 14px; vertical-align:top; background:#111827; }
.api_table th { background:#1e293b; color:#cbd5e1; }
.titleH1 { font-weight:700; text-align:center; background:#1d4ed8; color:#fff; padding:6px 10px; border-radius:4px; margin-bottom:6px; }
.param-title { font-weight:700; color:#94a3b8; font-size:12px; margin-top:12px; margin-bottom:4px; text-transform:uppercase; letter-spacing:.04em; }
.param-desc { margin-left:4px; word-break:break-word; color:#cbd5e1; }
input[type='text'], textarea {
  background:#0f172a; color:#e5e7eb;
  border:1px solid #475569; border-radius:4px; padding:5px 8px; font-size:13px;
}
input[type='text'] { width:600px; max-width:100%; }
input[type='text']:focus, textarea:focus { outline:none; border-color:#60a5fa; box-shadow:0 0 0 2px rgba(96,165,250,.2); }
textarea { width:700px; max-width:100%; height:90px; }
input[type='button'] {
  background:#1e293b; color:#e5e7eb; border:1px solid #475569; border-radius:4px;
  padding:4px 12px; font-size:13px; cursor:pointer;
}
input[type='button']:hover { background:#334155; }
.run-test { background:#2563eb !important; color:#fff !important; border-color:#2563eb !important; font-weight:700; }
.run-test:hover { background:#1d4ed8 !important; }
.param-row { margin:8px 0; }
.result-box { display:none; white-space:pre-wrap; background:#020617; color:#86efac; border:1px solid #334155; border-radius:4px; padding:10px; max-height:420px; overflow:auto; font-size:12px; font-family:monospace; margin-top:8px; }
.run-line { text-align:right; margin-top:12px; }
</style>
<?php
  require "{$base_dir}/head_end.php";
  require "{$base_dir}/body.php";
?>
<div class="container-fluid" style="padding:20px;">
  <h2>路線規劃 API 功能列表</h2>
  <div class="menu-box">
    API 分類：
    <select id="menu-kind"><option value="">-- 全部 --</option></select>
    <ul id="menu-list"></ul>
  </div>
  <table class="api_table">
    <thead><tr><th>API Method</th></tr></thead>
    <tbody id="api-body"></tbody>
  </table>
</div>

<script>
$(function(){
  var API_URL = 'api.php';

  myAjax_async_json('apiLists.txt?_t=' + new Date().getTime(), '', function(apis){
    var kinds = [], menu = [], rows = [];
    $.each(apis, function(mode, api){
      if (mode === 'API_INFO' || api.isHidden === true) return;
      var kind = api.kind || '未分類';
      if ($.inArray(kind, kinds) < 0) kinds.push(kind);
      menu.push('<li data-kind="' + esc(kind) + '"><a href="#title_' + esc(mode) + '">(' + esc(kind) + ') ' + esc(mode) + '</a>：' + esc(api.description || '') + '</li>');
      rows.push(buildRow(mode, api, kind));
    });
    $.each(kinds, function(i, k){ $('#menu-kind').append('<option value="' + esc(k) + '">' + esc(k) + '</option>'); });
    $('#menu-list').html(menu.join(''));
    $('#api-body').html(rows.join(''));
  });

  function buildRow(mode, api, kind) {
    var url = API_URL + '?mode=' + encodeURIComponent(mode);
    return '<tr data-kind="' + esc(kind) + '" id="row_' + esc(mode) + '">' +
      '<td>' +
        '<div class="titleH1" id="title_' + esc(mode) + '">' + esc(mode) + '</div>' +
        '<div class="param-title">API URL</div>' +
        '<div class="param-desc"><a target="_blank" href="' + url + '">' + url + '</a></div>' +
        '<div class="param-title">Description</div>' +
        '<div class="param-desc">' + esc(api.description || '') + '</div>' +
        '<div class="param-title">GET parameter</div>' +
        '<div class="param-desc">' + buildParams(mode, api.GET) + '</div>' +
        '<div class="param-title">POST parameter</div><div class="param-desc">' + (api.POST ? 'Yes' : 'Empty') + '</div>' +
        '<div class="param-title">FILE parameter</div><div class="param-desc">' + (api.FILE ? 'Yes' : 'Empty') + '</div>' +
        '<div class="param-title">Result Sample <input type="button" class="toggle-result" data-mode="' + esc(mode) + '" value="展開／縮合"></div>' +
        '<pre class="result-box" id="result_' + esc(mode) + '">' + esc(api.result_sample || api.result || '') + '</pre>' +
        '<div class="run-line"><input type="button" class="run-test" data-mode="' + esc(mode) + '" value="Run Test"></div>' +
      '</td>' +
    '</tr>';
  }

  function buildParams(mode, params) {
    if (!params) return 'Empty';
    var html = [];
    $.each(params, function(k, v){
      var sample = v.sample == null ? '' : String(v.sample);
      var input = sample.indexOf('\n') >= 0
        ? '<textarea class="param-input" data-mode="' + esc(mode) + '" data-key="' + esc(k) + '">' + esc(sample) + '</textarea>'
        : '<input type="text" class="param-input" data-mode="' + esc(mode) + '" data-key="' + esc(k) + '" value="' + esc(sample) + '">';
      html.push('<div class="param-row">【' + esc(k) + '】：' + esc(v.description || '') + '<br>' + input + '</div>');
    });
    return html.join('');
  }

  $('#menu-kind').on('change', function(){
    var kind = $(this).val();
    if (kind === '') { $('#menu-list li, #api-body tr').show(); }
    else { $('#menu-list li, #api-body tr').hide(); $('#menu-list li[data-kind="' + kind + '"], #api-body tr[data-kind="' + kind + '"]').show(); }
  });

  $(document).on('click', '.toggle-result', function(){
    $('#result_' + $(this).data('mode')).toggle();
  });

  $(document).on('click', '.run-test', function(){
    var mode = $(this).data('mode');
    var params = 'mode=' + encodeURIComponent(mode);
    $('.param-input[data-mode="' + mode + '"]').each(function(){
      params += '&' + encodeURIComponent($(this).data('key')) + '=' + encodeURIComponent($(this).val());
    });
    var result = $('#result_' + mode);
    result.text('載入中...').show();
    myAjax_async_json(API_URL, params, function(data){
      result.text(JSON.stringify(data, null, 2));
    });
  });

  function esc(str) { return $('<div>').text(str == null ? '' : String(str)).html(); }
});
</script>
</body>
</html>
