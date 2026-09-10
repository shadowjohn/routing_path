<?php

function expect_same($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

function request_route(array $params): array
{
    $runner = '$_REQUEST = json_decode(base64_decode($argv[1]), true); require $argv[2];';
    $command = [
        PHP_BINARY,
        '-d', 'display_errors=0',
        '-r', $runner,
        base64_encode(json_encode($params)),
        dirname(__DIR__) . '/api.php',
    ];
    $pipes = [];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit_code = proc_close($process);
    if ($exit_code !== 0) {
        fwrite(STDERR, "api.php failed: {$errors}\n");
        exit(1);
    }
    $response = json_decode($output, true);
    if (!is_array($response)) {
        fwrite(STDERR, "api.php did not return JSON: {$output}\n");
        exit(1);
    }
    return $response;
}

$base_request = [
    'mode' => 'routing_path',
    'travel_mode' => 'moto',
    'start_point' => '120.2784,22.6273',
    'end_point' => '120.302,22.627',
];

$legal = request_route($base_request);
expect_same('OK', $legal['status'] ?? null, 'legal routing should remain available');
expect_same('legal', $legal['direction_policy'] ?? null, 'legal routing should state its direction policy');
expect_same(false, $legal['is_demo_only'] ?? null, 'legal routing must not be marked demo-only');
expect_same(false, $legal['contains_reverse_edges'] ?? null, 'legal routing must not contain reverse edges');
expect_same(0, $legal['reversed_edge_count'] ?? null, 'legal routing must report zero reverse edges');
expect_same('route_moto', $legal['route_table'] ?? null, 'legal moto routing should keep its existing table');

$demo = request_route($base_request + ['direction_policy' => 'demo_bidirectional']);
expect_same('OK', $demo['status'] ?? null, 'demo routing should find a route');
expect_same('demo_bidirectional', $demo['direction_policy'] ?? null, 'demo routing should state its direction policy');
expect_same(true, $demo['is_demo_only'] ?? null, 'demo routing must be marked demo-only');
expect_same('route_moto_demo', $demo['route_table'] ?? null, 'demo moto routing should use its dedicated graph');
expect_same(
    ($demo['reversed_edge_count'] ?? -1) > 0,
    $demo['contains_reverse_edges'] ?? null,
    'reverse-edge summary flags should agree'
);

$invalid = request_route($base_request + ['direction_policy' => 'sideways']);
expect_same('ERROR', $invalid['status'] ?? null, 'unknown direction policies must be rejected');

$no_path = request_route(array_merge($base_request, ['end_point' => '119.566,23.57']));
expect_same('NO_PATH', $no_path['status'] ?? null, 'disconnected road networks must return NO_PATH');

echo "direction policy tests passed\n";
