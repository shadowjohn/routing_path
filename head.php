<?php
require_once __DIR__ . '/routing_config.php';
$routingConfig = routing_config();
?>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="<?= htmlspecialchars($routingConfig['easymap_script'], ENT_QUOTES, 'UTF-8') ?>"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script>
    window.ROUTING_CONFIG = <?= json_encode([
      'addressApiUrl' => $routingConfig['address_api_url'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

    function myAjax_async_json(url, data, callback) {
      if (!url) {
        console.warn('No API URL configured.');
        return $.Deferred().reject().promise();
      }
      return $.ajax({ url: url, data: data, method: data ? 'POST' : 'GET', dataType: 'json' })
        .done(callback)
        .fail(function(xhr) { console.error('API request failed:', xhr.status, url); });
    }

    function dialogMyBoxOn(title, message) {
      window.alert(title + '\n' + message);
    }
  </script>
