<?php
/** @var xPDOTransport $transport */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;
    $base = MODX_BASE_PATH . 'Extras/pbAuth/';

    $config = ['update' => ['symlinks' => false]];
    if (file_exists($base . '_build/config.inc.php')) {
        $config = include($base . '_build/config.inc.php');
    }

    if (!$config['update']['symlinks'] || !file_exists($base)) {
        return true;
    }

    /** @var xPDOCacheManager $cache */
    $cache = $modx->getCacheManager();

    $files = [
        'core' => [
            'link'   => $base . 'core/components/pbauth',
            'target' => MODX_CORE_PATH . 'components/pbauth/',
        ],
    ];

    foreach ($files as $key => $file) {
        if (file_exists($file['link']) && !is_link($file['link'])) {
            $cache->deleteTree($file['link'], ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]);
            $modx->log(modX::LOG_LEVEL_INFO, 'Removed installed dir, restoring symlink: ' . $file['link']);
        }

        if (!file_exists($file['link'])) {
            symlink($file['target'], $file['link']);
            $modx->log(modX::LOG_LEVEL_INFO, 'Symlink restored: ' . $file['link'] . ' → ' . $file['target']);
        }
    }
}
return true;