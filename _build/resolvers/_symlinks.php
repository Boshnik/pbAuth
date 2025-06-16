<?php

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;
    $cache = $modx->getCacheManager();

    if (!$cache) {
        $modx->log(modX::LOG_LEVEL_ERROR, 'Could not load CacheManager.');
        return false;
    }

    $base = MODX_BASE_PATH . 'Extras/pbAuth/';
    $config = ['update' => ['symlinks' => false]];
    if (file_exists($base . '_build/config.inc.php')) {
        $config = include($base . '_build/config.inc.php');
    }

    $source = $base . '_build/source/';
    $target = MODX_CORE_PATH . 'App/';

    if (!file_exists($source)) {
        $modx->log(modX::LOG_LEVEL_ERROR, "Could not find folder: $source");
        return false;
    }

    $files = [
//        'routes' => [
//            'source' => $source . 'routes',
//            'target' => $target . 'routes/',
//        ],
        'controllers' => [
            'source' => $source . 'Controllers/Auth',
            'target' => $target . 'Http/Controllers/Auth/',
        ],
        'elements' => [
            'source' => $source . 'elements/auth',
            'target' => $target . 'elements/auth/',
        ],
//        'lang' => [
//            'source' => $source . 'lang',
//            'target' => $target . 'lang/',
//        ],
    ];

    foreach ($files as $file) {
        if (is_link($file['source'])) {
            if (!$config['update']['symlinks']) {
                unlink($file['source']);
                $cache->copyTree($file['target'], $file['source']);
            }
        } else {
            if ($config['update']['symlinks'] ) {
                $cache->deleteTree(
                    $file['source'],
                    ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
                );
                symlink($file['target'], $file['source']);
            }
        }
    }
}

return true;
