<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */

/**
 * Раскладка файлов компонента в site-owned слой core/App/.
 *
 * Ни install, ни upgrade никогда не перезаписывают уже существующий файл:
 * попав в App/, файл принадлежит сайту. Чтобы деинсталляция не унесла с собой
 * чужое, компонент ведёт манифест — список путей, которые он реально положил,
 * с хешами на момент установки. Удаляются только те файлы, что с тех пор не
 * менялись; всё правленое остаётся сайту.
 */

if ($transport->xpdo) {
    $modx =& $transport->xpdo;

    $appFolders = ['Http', 'elements', 'lang'];
    // Больше не раскладывается в App/: роуты компонент подаёт из своего каталога
    // через Route::addRoutesPath() в bootstrap.php.
    $retired = ['routes/auth.php'];
    $core = MODX_CORE_PATH . 'components/pbauth/';
    $target = MODX_CORE_PATH . 'App/';
    $manifestFile = $target . '.pbauth-installed.json';

    $manifest = pbauthReadManifest($manifestFile);

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $copied = 0;
            $kept = 0;
            foreach ($appFolders as $folder) {
                $source = $core . $folder;
                if (!is_dir($source)) {
                    continue;
                }
                pbauthCopyMissing($source, $target . $folder, $folder, $manifest, $copied, $kept);
            }
            $retracted = pbauthRetract($retired, $core, $target, $manifest);
            pbauthWriteManifest($manifestFile, $manifest);
            $modx->log(modX::LOG_LEVEL_INFO, "[pbAuth] Скопировано файлов: {$copied}, оставлено файлов сайта: {$kept}.");
            foreach ($retracted as $relative) {
                $modx->log(modX::LOG_LEVEL_INFO, "[pbAuth] Удалена своя неизменённая копия App/{$relative} — файл теперь подаётся из компонента.");
            }
            break;

        case xPDOTransport::ACTION_UNINSTALL:
            $removed = 0;
            $kept = 0;
            foreach ($manifest as $relative => $hash) {
                $path = $target . $relative;
                if (!is_file($path)) {
                    continue;
                }
                if (sha1_file($path) !== $hash) {
                    $kept++;
                    continue;
                }
                if (unlink($path)) {
                    $removed++;
                }
            }
            foreach ($appFolders as $folder) {
                pbauthRemoveEmptyDirs($target . $folder);
            }
            if (is_file($manifestFile)) {
                unlink($manifestFile);
            }
            $modx->log(modX::LOG_LEVEL_INFO, "[pbAuth] Удалено файлов: {$removed}, оставлено изменённых сайтом: {$kept}.");
            break;
    }
}

function pbauthReadManifest(string $file): array
{
    if (!is_file($file)) {
        return [];
    }

    $data = json_decode((string)file_get_contents($file), true);

    return is_array($data) ? array_filter($data, 'is_string') : [];
}

function pbauthWriteManifest(string $file, array $manifest): void
{
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    ksort($manifest);
    file_put_contents($file, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Копирует только отсутствующие файлы и ведёт учёт того, что принадлежит компоненту.
 *
 * Файл, совпадающий с поставочным байт в байт, компонент считает своим даже без
 * записи в манифесте — так подхватываются установки, сделанные до его появления.
 * Файл, изменившийся после установки, из манифеста вычёркивается: он теперь сайта.
 */
function pbauthCopyMissing(
    string $sourceDir,
    string $targetDir,
    string $prefix,
    array &$manifest,
    int &$copied,
    int &$kept
): void {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $relative = $prefix . '/' . str_replace(
            DIRECTORY_SEPARATOR,
            '/',
            ltrim(substr($file->getPathname(), strlen($sourceDir)), DIRECTORY_SEPARATOR)
        );
        $targetPath = $targetDir . substr($relative, strlen($prefix));

        if (!file_exists($targetPath)) {
            $targetFolder = dirname($targetPath);
            if (!is_dir($targetFolder)) {
                mkdir($targetFolder, 0755, true);
            }
            if (copy($file->getPathname(), $targetPath)) {
                $manifest[$relative] = sha1_file($targetPath);
                $copied++;
            }
            continue;
        }

        $targetHash = sha1_file($targetPath);

        if (isset($manifest[$relative])) {
            if ($manifest[$relative] !== $targetHash) {
                unset($manifest[$relative]);
                $kept++;
            }
            continue;
        }

        if ($targetHash === sha1_file($file->getPathname())) {
            $manifest[$relative] = $targetHash;
        } else {
            $kept++;
        }
    }
}

/**
 * Убирает из App/ файлы, которые компонент туда больше не кладёт.
 *
 * Удаляется только собственная нетронутая копия: та, что числится в манифесте с
 * прежним хешем, либо совпадает с поставочной байт в байт. Правленый сайтом файл
 * остаётся — bootstrap.php увидит его и уступит ему дорогу.
 *
 * @return string[] пути, которые действительно удалены
 */
function pbauthRetract(array $retired, string $core, string $target, array &$manifest): array
{
    $removed = [];

    foreach ($retired as $relative) {
        $path = $target . $relative;
        if (!is_file($path)) {
            unset($manifest[$relative]);
            continue;
        }

        $hash = sha1_file($path);
        $shipped = $core . $relative;
        $isOurs = (isset($manifest[$relative]) && $manifest[$relative] === $hash)
            || (is_file($shipped) && sha1_file($shipped) === $hash);

        if ($isOurs && unlink($path)) {
            $removed[] = $relative;
        }
        unset($manifest[$relative]);
    }

    return $removed;
}

function pbauthRemoveEmptyDirs(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir() && !(new FilesystemIterator($item->getPathname()))->valid()) {
            rmdir($item->getPathname());
        }
    }

    if (!(new FilesystemIterator($directory))->valid()) {
        rmdir($directory);
    }
}

return true;
