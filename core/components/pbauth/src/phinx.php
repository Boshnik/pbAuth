<?php

$configPath = dirname(__DIR__, 4) . '/config.core.php';
if (!file_exists($configPath)) {
    die("config.core.php not found at: {$configPath}\n");
}

require_once $configPath;
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
require_once MODX_CORE_PATH . 'components/pageblocks/vendor/autoload.php';

/** @var MODX\Revolution\modX $modx */
$modx = new modX();
$modx->initialize('mgr');

$config = $modx->getConnection()->config;
$environment = 'production';
$database = [
    'adapter' => $config['dbtype'],
    'host'    => $config['host'],
    'name'    => $config['dbname'],
    'user'    => $config['username'],
    'pass'    => $config['password'],
    'port'    => '3306',
    'charset' => $config['charset'] ?? 'utf8mb4',
    'table_prefix' => $config['table_prefix'],
];

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/Database/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/Database/seeds',
    ],
    'environments' => [
        // Own migration log: pbAuth versions move independently of PageBlocks.
        'default_migration_table' => $config['table_prefix'] . 'pba_migrations',
        'default_environment' => $environment,
        'development' => $database,
        'production' => $database,
    ],
    'version_order' => 'creation',
];
