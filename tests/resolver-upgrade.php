<?php
/**
 * Обновление доносит правки шаблонов до своих копий в App/ и называет чужие.
 *
 * Было так: резолвер копировал файл только когда его в `App/` нет, и ветка
 * upgrade ничем не отличалась от install. А рендерится именно копия из `App/`:
 * шаблоны зовутся через провайдер `file:`, заведённый на `App/elements/`, и
 * каталог `elements/` внутри компонента при рендере не читается никогда.
 * Значит правки шаблонов получала только чистая установка, а обновляемый сайт
 * оставался с прежней разметкой - на этом споткнулся выпуск 1.1.1-pl, где все
 * правки безопасности лежат в чанках форм.
 *
 * Стало так: при upgrade перезаписывается своя нетронутая копия - та, что
 * числится в манифесте с прежним хешем. Правленый сайтом файл остаётся ему и
 * попадает в `kept`, откуда его называют в логе по имени: обновление его не
 * исправило, и разбираться придётся вручную.
 *
 * Три случая проверяются по отдельности, потому что каждый ломается своим
 * способом: не копировать отсутствующий - установка пустая; не обновлять свой -
 * правки не доезжают; перезаписать правленый - сайту молча сносят вёрстку.
 *
 * Проверка работает с настоящими функциями из `_build/resolvers/files.php` на
 * временных каталогах. База не нужна: ветка, которой нужен `modX`, закрыта
 * заглушкой `$transport->xpdo = null`.
 *
 *   php tests/resolver-upgrade.php
 */

$fail = 0;

function check(string $name, bool $ok, string $detail = ''): void
{
    global $fail;

    // Не printf: %-56s считает байты, и кириллица разъезжается по столбцам.
    $pad = str_repeat(' ', max(1, 60 - mb_strlen($name)));

    echo $name, $pad, $ok ? 'ok' : 'ПРОВАЛ' . ($detail !== '' ? " — {$detail}" : ''), PHP_EOL;

    if (!$ok) {
        $fail++;
    }
}

// Заглушка транспорта: файл резолвера весь исполняемый код прячет за
// `if ($transport->xpdo)`, поэтому с пустым xpdo он только объявит функции.
$transport = new stdClass();
$transport->xpdo = null;

require __DIR__ . '/../_build/resolvers/files.php';

check(
    'функции резолвера объявлены',
    function_exists('pbauthCopyMissing') && function_exists('pbauthReadManifest'),
    'подключение _build/resolvers/files.php изменилось'
);

$root = sys_get_temp_dir() . '/pbauth-resolver-' . getmypid();

/** Чистая площадка: поставка с одним чанком и пустой слой сайта. */
function stage(string $root, string $shipped): array
{
    rmrf($root);

    $source = $root . '/components/pbauth/elements';
    $targetDir = $root . '/App/elements';

    mkdir($source . '/auth/chunks', 0777, true);
    file_put_contents($source . '/auth/chunks/form.login.tpl', $shipped);

    return [$source, $targetDir, $targetDir . '/auth/chunks/form.login.tpl'];
}

function rmrf(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($path);
}

function emptyReport(): array
{
    return ['copied' => 0, 'updated' => 0, 'kept' => [], 'failed' => []];
}

$shipped = '{$user.username|escape}';
$ours = '{$user.username}';
$edited = '<div class="site">{$user.username}</div>';
$relative = 'elements/auth/chunks/form.login.tpl';

echo '--- Случай 1: файла в App/ нет ---', PHP_EOL;

[$source, $targetDir, $targetPath] = stage($root, $shipped);
$manifest = [];
$report = emptyReport();
pbauthCopyMissing($source, $targetDir, 'elements', $manifest, $report, true);

check('файл скопирован', is_file($targetPath), 'установка осталась бы пустой');
check('содержимое поставочное', @file_get_contents($targetPath) === $shipped);
check('счётчик copied равен 1', $report['copied'] === 1, "получено {$report['copied']}");
check('путь попал в манифест', ($manifest[$relative] ?? '') === sha1($shipped));
check('файл не назван оставленным', $report['kept'] === [], 'лог обвинил бы сайт зря');

echo PHP_EOL, '--- Случай 2: наша нетронутая копия ---', PHP_EOL;

[$source, $targetDir, $targetPath] = stage($root, $shipped);
mkdir(dirname($targetPath), 0777, true);
file_put_contents($targetPath, $ours);
$manifest = [$relative => sha1($ours)];
$report = emptyReport();
pbauthCopyMissing($source, $targetDir, 'elements', $manifest, $report, true);

// Главное утверждение: ровно его отсутствие и означало, что правки
// безопасности не доедут ни до одного обновляемого сайта.
check(
    'upgrade перезаписал своей поставочной',
    @file_get_contents($targetPath) === $shipped,
    'правки версии до сайта не доехали'
);
check('счётчик updated равен 1', $report['updated'] === 1, "получено {$report['updated']}");
check('хеш в манифесте обновлён', ($manifest[$relative] ?? '') === sha1($shipped));
check('файл не назван оставленным', $report['kept'] === [], 'своя копия не файл сайта');

// Установка - не обновление: там перезаписывать нечего, и трогать существующий
// файл незачем. Разделение сторожится, чтобы install не начал переписывать.
[$source, $targetDir, $targetPath] = stage($root, $shipped);
mkdir(dirname($targetPath), 0777, true);
file_put_contents($targetPath, $ours);
$manifest = [$relative => sha1($ours)];
$report = emptyReport();
pbauthCopyMissing($source, $targetDir, 'elements', $manifest, $report, false);

check('install ту же копию оставил', @file_get_contents($targetPath) === $ours);
check('счётчик updated равен 0', $report['updated'] === 0, "получено {$report['updated']}");
check('путь остался в манифесте', isset($manifest[$relative]), 'деинсталляция его бы не унесла');

echo PHP_EOL, '--- Случай 3: файл правлен сайтом ---', PHP_EOL;

[$source, $targetDir, $targetPath] = stage($root, $shipped);
mkdir(dirname($targetPath), 0777, true);
file_put_contents($targetPath, $edited);
$manifest = [$relative => sha1($ours)];
$report = emptyReport();
pbauthCopyMissing($source, $targetDir, 'elements', $manifest, $report, true);

check('правка сайта на месте', @file_get_contents($targetPath) === $edited, 'сайту снесли вёрстку');
check('счётчик updated равен 0', $report['updated'] === 0, "получено {$report['updated']}");
check('файл назван оставленным', $report['kept'] === [$relative], 'в логе его бы не было');
check('путь вычеркнут из манифеста', !isset($manifest[$relative]), 'файл теперь сайта');

// Тот же случай, но манифеста нет вовсе: установка до его появления. Файл с
// поставочным не совпадает, значит правлен, и обращаться с ним надо так же.
[$source, $targetDir, $targetPath] = stage($root, $shipped);
mkdir(dirname($targetPath), 0777, true);
file_put_contents($targetPath, $edited);
$manifest = [];
$report = emptyReport();
pbauthCopyMissing($source, $targetDir, 'elements', $manifest, $report, true);

check('без манифеста правка тоже цела', @file_get_contents($targetPath) === $edited);
check('файл назван оставленным', $report['kept'] === [$relative]);

echo PHP_EOL, '--- Копия, уже совпадающая с поставочной ---', PHP_EOL;

[$source, $targetDir, $targetPath] = stage($root, $shipped);
mkdir(dirname($targetPath), 0777, true);
file_put_contents($targetPath, $shipped);
$manifest = [];
$report = emptyReport();
pbauthCopyMissing($source, $targetDir, 'elements', $manifest, $report, true);

check('путь подхвачен в манифест', ($manifest[$relative] ?? '') === sha1($shipped), 'деинсталляция бы его не унесла');
check('перезаписи не было', $report['updated'] === 0, "получено {$report['updated']}");
check('оставленным не назван', $report['kept'] === [], 'лог обвинил бы сайт зря');

echo PHP_EOL, '--- Оставленные файлы называются в логе ---', PHP_EOL;

// Строки в лог пишет ветка, которой нужен `modX`, поэтому здесь сторожится
// текст: без имени файла в строке сайт с правленым чанком остался бы с дырой,
// о которой никто не узнает.
$resolver = (string) file_get_contents(__DIR__ . '/../_build/resolvers/files.php');

check(
    'есть цикл по kept с подстановкой пути',
    (bool) preg_match('~foreach \(\$report\[.kept.\] as \$relative\).*?App/\{\$relative\}~s', $resolver),
    'оставленные файлы не называются по имени'
);
check(
    'строка идёт уровнем WARN',
    (bool) preg_match('~foreach \(\$report\[.kept.\].*?LOG_LEVEL_WARN~s', $resolver),
    'в общем потоке INFO такую строку не заметят'
);
check(
    'неудачная перезапись тоже называется',
    (bool) preg_match('~foreach \(\$report\[.failed.\] as \$relative\).*?LOG_LEVEL_ERROR~s', $resolver),
    'файл остался без правок молча'
);

rmrf($root);

echo PHP_EOL, $fail === 0
    ? 'Все проверки прошли'
    : "Провалов: {$fail}", PHP_EOL;

exit($fail === 0 ? 0 : 1);
