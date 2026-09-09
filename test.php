<?php
require_once __DIR__ . '/routing_config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $config = routing_config();
    if (!is_file($config['database_path'])) {
        throw new RuntimeException('路由資料庫不存在');
    }

    $pdo = new PDO('sqlite:' . $config['database_path']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->query('SELECT load_extension(' . $pdo->quote($config['spatialite_extension']) . ');');
    $pdo->exec('PRAGMA synchronous = off;');

    echo json_encode([
        'status' => 'OK',
        'spatialite_version' => $pdo->query('SELECT spatialite_version()')->fetchColumn(),
        'roads' => (int)$pdo->query('SELECT COUNT(*) FROM roads')->fetchColumn(),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $ex) {
    http_response_code(500);
    echo json_encode(['status' => 'ERROR', 'msg' => '路由資料庫健康檢查失敗'], JSON_UNESCAPED_UNICODE);
}
