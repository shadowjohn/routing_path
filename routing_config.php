<?php

function routing_config(): array
{
    $config = [
        'easymap_script' => 'assets/easymap/easymap.js',
        'address_api_url' => '',
        'database_path' => __DIR__ . '/taiwan_routing_v2.sqlite',
        'spatialite_extension' => getenv('ROUTING_SPATIALITE_EXTENSION') ?: 'mod_spatialite',
    ];

    $localConfig = __DIR__ . '/config.local.php';
    if (is_file($localConfig)) {
        $overrides = require $localConfig;
        if (is_array($overrides)) {
            $config = array_replace($config, $overrides);
        }
    }

    return $config;
}
