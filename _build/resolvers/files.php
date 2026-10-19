<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */

/**
 * Раскладка файлов компонента в site-owned слой core/App/.
 *
 * Правленый сайтом файл в App/ принадлежит сайту, и компонент его не трогает.
 * Чтобы отличить правленый от своего, компонент ведёт манифест — список путей,
 * которые он реально положил, с хешами на момент установки.
 *
 * Своя нетронутая копия — та, что числится в манифесте с прежним хешем, — при
 * upgrade перезаписывается поставочной. Иначе правки компонента доезжают только
 * до чистых установок: рендерится именно копия из App/ (шаблоны зовутся через
 * провайдер `file:`, заведённый на `App/elements/`), а каталог elements/ внутри
 * компонента при рендере не читается никогда. Выпуск 1.1.1-pl на этом и
 * споткнулся — правки экранирования не дошли бы ни до одного обновляемого
 * сайта, то есть ровно до тех, ради которых затевались.
 *
 * Оставленный файл сайта называется в логе по имени: обновление его не
 * исправило, и разобраться с ним придётся вручную. Молча оставить такой файл
 * нельзя — сайт с правленым `form.login.tpl` остался бы с дырой, о которой
 * никто не узнает.
 *
 * Деинсталляция удаляет только те файлы, что с установки не менялись; всё
 * правленое остаётся сайту.
 */

if ($transport->xpdo) {
    $modx =& $transport->xpdo;

    // В App/ раскладывается только то, что каждый сайт всё равно рисует по-своему.
    $appFolders = ['elements', 'lang'];
    // Роуты и контроллеры теперь живут в компоненте: роуты подаются через
    // Route::addRoutesPath() из bootstrap.php, контроллеры настраиваются через
    // App/config/pbauth.php. Свои нетронутые копии в App/ компонент убирает.
    $retired = array_merge(['routes/auth.php'], array_map(
        fn($name) => "Http/Controllers/Auth/{$name}Controller.php",
        ['Auth', 'ChangePassword', 'ConfirmPassword', 'ForgotPassword', 'Login', 'Profile', 'Register', 'ResetPassword']
    ));
    $core = MODX_CORE_PATH . 'components/pbauth/';
    $target = MODX_CORE_PATH . 'App/';
    $manifestFile = $target . '.pbauth-installed.json';

    $manifest = pbauthReadManifest($manifestFile);

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            // Перезапись своих копий — только при обновлении. При установке
            // перезаписывать нечего: либо файла нет, либо он сайта.
            $isUpgrade = $options[xPDOTransport::PACKAGE_ACTION] === xPDOTransport::ACTION_UPGRADE;
            $report = ['copied' => 0, 'updated' => 0, 'kept' => [], 'failed' => []];
            foreach ($appFolders as $folder) {
                $source = $core . $folder;
                if (!is_dir($source)) {
                    continue;
                }
                pbauthCopyMissing($source, $target . $folder, $folder, $manifest, $report, $isUpgrade);
            }
            // Пустая заготовка конфига, чтобы сайту не пришлось выяснять, как он
            // называется и где лежит. Все секции в ней закомментированы, так что
            // до первой правки она ничего не меняет.
            pbauthSeedFile(
                $core . 'docs/pbauth.config.example.php',
                $target . 'config/pbauth.php',
                'config/pbauth.php',
                $manifest,
                $report
            );
            $retracted = pbauthRetract($retired, $core, $target, $manifest);
            pbauthWriteManifest($manifestFile, $manifest);
            $modx->log(modX::LOG_LEVEL_INFO, sprintf(
                '[pbAuth] Скопировано файлов: %d, обновлено своих копий: %d, оставлено файлов сайта: %d.',
                $report['copied'],
                $report['updated'],
                count($report['kept'])
            ));
            // По файлу на строку, и это не болтливость: каждая такая строка —
            // место, куда правки версии не доехали.
            foreach ($report['kept'] as $relative) {
                $modx->log(
                    modX::LOG_LEVEL_WARN,
                    "[pbAuth] App/{$relative} правлен сайтом и оставлен как есть — правки этой версии в него не попали."
                    . " Сверьте его с core/components/pbauth/{$relative} вручную."
                );
            }
            foreach ($report['failed'] as $relative) {
                $modx->log(
                    modX::LOG_LEVEL_ERROR,
                    "[pbAuth] App/{$relative} не удалось перезаписать — файл остался прежним, правки этой версии в него не попали."
                    . ' Проверьте права на файл и повторите обновление.'
                );
            }
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
 * Раскладывает файлы и ведёт учёт того, что принадлежит компоненту.
 *
 * Три случая на каждый файл, и они не сводятся друг к другу:
 *
 *   - файла в App/ нет — копируется, путь с хешем идёт в манифест;
 *   - файл есть и это наша нетронутая копия (хеш совпал с манифестом) — при
 *     upgrade перезаписывается поставочным, при install остаётся как есть;
 *   - файл есть и правлен сайтом — остаётся, из манифеста вычёркивается (он
 *     теперь сайта) и попадает в `kept`, чтобы его назвали в логе.
 *
 * Файл, совпадающий с поставочным байт в байт, компонент считает своим даже без
 * записи в манифесте — так подхватываются установки, сделанные до его появления.
 */
function pbauthCopyMissing(
    string $sourceDir,
    string $targetDir,
    string $prefix,
    array &$manifest,
    array &$report,
    bool $isUpgrade
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
                $report['copied']++;
            }
            continue;
        }

        $targetHash = sha1_file($targetPath);
        $sourceHash = sha1_file($file->getPathname());

        // Уже то же самое: перезаписывать нечего, но файл наш — числим в
        // манифесте, чтобы деинсталляция знала, что его можно унести.
        if ($targetHash === $sourceHash) {
            $manifest[$relative] = $targetHash;
            continue;
        }

        if (isset($manifest[$relative]) && $manifest[$relative] === $targetHash) {
            if (!$isUpgrade) {
                continue;
            }
            if (copy($file->getPathname(), $targetPath)) {
                $manifest[$relative] = $sourceHash;
                $report['updated']++;
            } else {
                // Файл остался прежним, то есть без правок этой версии.
                // Молчать об этом нельзя так же, как о правленом сайтом.
                $report['failed'][] = $relative;
            }
            continue;
        }

        // Правлен сайтом: либо числится в манифесте с другим хешем, либо не
        // числится вовсе и с поставочным не совпадает.
        unset($manifest[$relative]);
        $report['kept'][] = $relative;
    }
}

/**
 * Кладёт один файл под своим именем, если его ещё нет.
 *
 * От pbauthCopyMissing отличается тем, что имя в App/ не совпадает с исходным:
 * образец конфига становится боевым конфигом сайта.
 */
function pbauthSeedFile(
    string $source,
    string $targetPath,
    string $relative,
    array &$manifest,
    array &$report
): void {
    if (!is_file($source) || file_exists($targetPath)) {
        return;
    }

    $folder = dirname($targetPath);
    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }

    if (copy($source, $targetPath)) {
        $manifest[$relative] = sha1_file($targetPath);
        $report['copied']++;
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
