<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */

if (!$transport->xpdo) {
    return false;
}

$modx =& $transport->xpdo;
$action = $options[xPDOTransport::PACKAGE_ACTION];

// Phinx itself comes from PageBlocks: pbAuth declares no vendor of its own.
$phinxBin    = MODX_CORE_PATH . 'components/pageblocks/vendor/bin/phinx';
$phinxConfig = MODX_CORE_PATH . 'components/pbauth/src/phinx.php';

if (!file_exists($phinxBin)) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[pbAuth] Phinx binary not found: ' . $phinxBin . '. Is PageBlocks installed?');
    return false;
}

switch ($action) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:
        $command = sprintf(
            'php %s migrate --configuration=%s --environment=production 2>&1',
            escapeshellarg($phinxBin),
            escapeshellarg($phinxConfig)
        );
        $output = shell_exec($command);
        $modx->log(modX::LOG_LEVEL_INFO, '[pbAuth] Phinx migrate: ' . $output);
        break;

    case xPDOTransport::ACTION_UNINSTALL:
        $command = sprintf(
            'php %s rollback --configuration=%s --environment=production --target=0 2>&1',
            escapeshellarg($phinxBin),
            escapeshellarg($phinxConfig)
        );
        $output = shell_exec($command);
        $modx->log(modX::LOG_LEVEL_INFO, '[pbAuth] Phinx rollback: ' . $output);
        break;
}

return true;
