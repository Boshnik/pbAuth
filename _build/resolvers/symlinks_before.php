<?php

/** @var xPDOTransport $transport */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;
    $base = MODX_BASE_PATH . 'Extras/pbAuth/';

    $files = [
        $base . 'core/components/pbauth',
    ];

    foreach ($files as $path) {
        if (is_link($path)) {
            $modx->log(modX::LOG_LEVEL_INFO, 'Removing symlink before install: ' . $path);
            unlink($path);
        }
    }
}
return true;