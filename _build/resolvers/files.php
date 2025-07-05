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

    $core = MODX_CORE_PATH . 'components/pbauth/';
    $target = MODX_CORE_PATH . 'App/';
    $folders = array_filter(glob($core . '*'), 'is_dir');

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
            foreach ($folders as $folder) {
                $folderName = basename($folder);
                if ($folderName === 'docs') {
                    continue;
                }

                $targetPath = $target . $folderName . '/';
                $cache->copyTree($folder, $targetPath);
            }
            break;
        case xPDOTransport::ACTION_UPGRADE:
            foreach ($folders as $folder) {
                $folderName = basename($folder);
                if ($folderName === 'docs') {
                    continue;
                }

                $targetPath = $target . $folderName . '/';
                copyMissingFiles($folder, $targetPath);
            }
            break;
        case xPDOTransport::ACTION_UNINSTALL:
            cleanFiles($cache, $target);
            break;
    }
}

function copyMissingFiles(string $sourceDir, string $targetDir): void
{
    if (!is_dir($sourceDir)) {
        throw new RuntimeException("Source directory does not exist: $sourceDir");
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $relativePath = substr($file->getPathname(), strlen($sourceDir));
            $targetPath = $targetDir . $relativePath;

            if (!file_exists($targetPath)) {
                $targetFolder = dirname($targetPath);
                if (!is_dir($targetFolder)) {
                    mkdir($targetFolder, 0755, true);
                }

                copy($file->getPathname(), $targetPath);
            }
        }
    }
}

function cleanFiles($cache, string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        $path = $item->getPathname();

        if ($item->isFile()) {
            if ($item->getFilename() === 'auth.php') {
                unlink($path);
            }
        }

        if ($item->isDir()) {
            $folderName = $item->getFilename();
            if ($folderName === 'auth' || $folderName === 'Auth') {
                $cache->deleteTree($path, ['deleteTop' => true, 'extensions' => []]);
            }
        }
    }
}

return true;