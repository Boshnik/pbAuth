<?php
/**
 * Установка пакета не сносит рабочее дерево компонента.
 *
 * `_build/resolvers/symlinks_after.php` восстанавливает удобство dev-чекаута:
 * там `Extras/pbAuth/core|assets/components/pbauth` - симлинки в установленный
 * компонент, файловый резолвер их затирает копиями, и ссылки ставятся обратно.
 * Для этого случая всё верно.
 *
 * Поломка - когда по тому же пути лежит обычная копия чекаута. А именно так
 * пакет и собирают: исходники лежат на сайте, сборка ставит пакет на него же, и
 * резолвер удалял найденный каталог целиком (`deleteTree` с `deleteTop`), чтобы
 * освободить место под ссылку. То есть установка съедала дерево с исходниками -
 * 81 файл компонента и всё, что в нём не закоммичено.
 *
 * Теперь правило без условий: путь, который не является симлинком, резолвер не
 * удаляет никогда. Нашёл настоящий каталог - оставил, сказал в лог почему,
 * ссылку не поставил. Флаг `update.symlinks` на это не влияет: он решает,
 * ставить ли ссылки, а не можно ли сносить исходники. Одного флага и не хватило
 * бы - конфиг резолвер читает `include`'ом из чекаута НА САЙТЕ, а не из пакета,
 * так что на сайте с уже лежащей копией `true` останется `true`. Поэтому стенд
 * здесь свой конфиг с `true` и кладёт: проверяется худший случай, а не значение
 * из репозитория.
 *
 * Без базы и без MODX: стенд - временные каталоги, `modX` и кеш-менеджер -
 * заглушки в этом же файле. Настоящий `deleteTree()` не зовётся ни в одном
 * случае: заглушка только записывает просьбу, и утверждения смотрят на этот
 * список.
 *
 * Резолвер подключается `require`'ом по разу на случай - он весь исполняемый, в
 * нём нет функций, которые можно было бы позвать отдельно (этим он отличается
 * от `files.php` и `tests/resolver-upgrade.php`). Отсюда два следствия: стенд
 * один на весь прогон, потому что путь к нему резолвер берёт из констант
 * `MODX_*`, а его содержимое пересобирается перед каждым случаем; и имена
 * переменных здесь свои - `$stand`, а не `$base`, иначе резолвер затрёт их
 * своими, ведь область видимости у него с этим файлом общая.
 *
 *   php tests/resolver-symlinks.php
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

/**
 * Снести дерево, не ходя по ссылкам: ссылку снимаем, а не её цель.
 *
 * Своя, а не как в `resolver-upgrade.php` через `RecursiveDirectoryIterator`:
 * тот в симлинк на каталог заходит и вычистил бы установленный компонент через
 * ссылку, которую этот тест как раз и проверяет.
 */
function rmrf(string $path): void
{
    if (is_link($path) || is_file($path)) {
        @unlink($path);

        return;
    }

    if (!is_dir($path)) {
        return;
    }

    foreach (array_diff((array) scandir($path), ['.', '..']) as $item) {
        rmrf($path . '/' . $item);
    }

    @rmdir($path);
}

/** Уровни лога и сам лог - всё, что резолвер спрашивает у MODX. */
class modX
{
    public const LOG_LEVEL_FATAL = 0;
    public const LOG_LEVEL_ERROR = 1;
    public const LOG_LEVEL_WARN = 2;
    public const LOG_LEVEL_INFO = 3;
    public const LOG_LEVEL_DEBUG = 4;

    /** @var array<int, string> */
    public array $logged = [];

    public function __construct(public PbAuthRecordingCacheManager $cacheManager)
    {
    }

    public function log($level, $message, $target = '', $def = '', $file = '', $line = ''): void
    {
        $this->logged[] = (string) $message;
    }

    public function getCacheManager(): PbAuthRecordingCacheManager
    {
        return $this->cacheManager;
    }
}

/**
 * Кеш-менеджер, который только записывает просьбу об удалении.
 *
 * Ничего не удаляет намеренно: проверяется как раз то, что резолвер
 * `deleteTree()` не просит, и настоящий не должен сработать ни при какой
 * ошибке в самом тесте.
 */
class PbAuthRecordingCacheManager
{
    /** @var array<int, string> */
    public array $deleted = [];

    public function deleteTree(string $path, array $options = []): bool
    {
        $this->deleted[] = $path;

        return true;
    }
}

$stand = sys_get_temp_dir() . '/pbauth-symlinks-' . getmypid();

define('MODX_BASE_PATH', $stand . '/');
define('MODX_CORE_PATH', $stand . '/core/');
define('MODX_ASSETS_PATH', $stand . '/assets/');

$recorder = new PbAuthRecordingCacheManager();
$transport = new stdClass();
$transport->xpdo = new modX($recorder);

// symlink() на занятом пути ругается предупреждением, а не исключением, и в
// обычном прогоне его легко не заметить. Поэтому они собираются: пустой список
// - такое же утверждение, как остальные. Сторож не полный: на битой ссылке
// symlink() не предупреждает вовсе, и тот случай проверяется по следам на диске
// (случай 5).
$warnings = [];
set_error_handler(static function (int $no, string $message) use (&$warnings): bool {
    $warnings[] = $message;

    return true;
});

/**
 * Пересобрать стенд и прогнать резолвер.
 *
 * @param array<string, string> $state что лежит по пути
 *        `Extras/pbAuth/<ключ>/components/pbauth`: dir, link, broken, missing
 * @param bool $symlinks значение `update.symlinks` в конфиге НА СТЕНДЕ
 */
function run(array $state, bool $symlinks = true): void
{
    global $stand, $transport, $recorder, $warnings;

    rmrf($stand);

    $transport->xpdo->logged = [];
    $recorder->deleted = [];
    $warnings = [];

    // Установленный компонент: цель ссылок.
    foreach (['core', 'assets'] as $key) {
        mkdir($stand . '/' . $key . '/components/pbauth', 0777, true);
    }

    $checkout = $stand . '/Extras/pbAuth';
    mkdir($checkout . '/_build', 0777, true);
    file_put_contents(
        $checkout . '/_build/config.inc.php',
        "<?php\n\nreturn ['update' => ['symlinks' => " . ($symlinks ? 'true' : 'false') . "]];\n"
    );

    foreach ($state as $key => $what) {
        $link = $checkout . '/' . $key . '/components/pbauth';

        if ($what === 'dir') {
            // Метка в подкаталоге, а не в корне: `deleteTop` сносит и то и
            // другое, но вложенный файл видно и в полуснесённом дереве.
            mkdir($link . '/src', 0777, true);
            file_put_contents($link . '/src/marker.txt', 'исходники');
            continue;
        }

        mkdir(dirname($link), 0777, true);

        if ($what === 'link') {
            symlink($stand . '/' . $key . '/components/pbauth/', $link);
        }

        if ($what === 'broken') {
            symlink($stand . '/' . $key . '/components/pbauth-gone/', $link);
        }
    }

    // Резолвер трогал эти пути в этом же процессе, а stat PHP кеширует.
    clearstatcache();

    require __DIR__ . '/../_build/resolvers/symlinks_after.php';
}

/** Что стало с путём. */
function state(string $key): array
{
    global $stand;

    $link = $stand . '/Extras/pbAuth/' . $key . '/components/pbauth';

    clearstatcache(true, $link);

    return [
        'is_link' => is_link($link),
        'exists'  => is_link($link) || file_exists($link),
        'target'  => is_link($link) ? readlink($link) : null,
        // Файл из рабочего дерева: он отвечает на вопрос «каталог цел», а не
        // только «путь на месте».
        'marker'  => is_file($link . '/src/marker.txt'),
    ];
}

echo '--- Случай 1: по пути настоящий каталог с исходниками ---', PHP_EOL;

run(['core' => 'dir', 'assets' => 'missing']);
$core = state('core');

// Главное утверждение всего файла: ровно его отсутствие и означало, что
// установка пакета на свой же сайт сносит рабочее дерево компонента.
check('удаления не просили', $recorder->deleted === [], 'дерево с исходниками снесли бы');
check('рабочее дерево цело', $core['marker'], 'каталог пуст или снесён');
check('каталог не подменён ссылкой', !$core['is_link']);
check('причина названа в логе', str_contains(implode("\n", $transport->xpdo->logged), 'лежит настоящий каталог'));
check('предупреждений нет', $warnings === [], implode('; ', $warnings));

echo PHP_EOL, '--- Случай 2: по пути симлинк dev-чекаута ---', PHP_EOL;

run(['core' => 'link', 'assets' => 'missing']);
$core = state('core');

check('ссылка на месте', $core['is_link'], 'ссылку сняли, dev-чекаут сломан');
check('цель та же', $core['target'] === $stand . '/core/components/pbauth/');
check('удаления не просили', $recorder->deleted === []);
check('предупреждений нет', $warnings === [], implode('; ', $warnings));

echo PHP_EOL, '--- Случай 3: пути нет - ради чего резолвер и существует ---', PHP_EOL;

run(['core' => 'missing', 'assets' => 'missing']);
$core = state('core');
$assets = state('assets');

check('ссылка на core создана', $core['is_link'], 'dev-чекаут остался без ссылки');
check('цель core верна', $core['target'] === $stand . '/core/components/pbauth/');
check('ссылка на assets создана', $assets['is_link']);
check('цель assets верна', $assets['target'] === $stand . '/assets/components/pbauth/');
check('удаления не просили', $recorder->deleted === []);
check('предупреждений нет', $warnings === [], implode('; ', $warnings));

echo PHP_EOL, '--- Случай 4: настоящий каталог не прерывает обход ---', PHP_EOL;

// `assets` в наборе идёт первым, и если на нём резолвер выйдет по `return`,
// `core` останется без ссылки молча. Поэтому случай разведён по путям.
run(['assets' => 'dir', 'core' => 'missing']);
$core = state('core');
$assets = state('assets');

check('рабочее дерево assets цело', $assets['marker']);
check('ссылка на core всё равно создана', $core['is_link'], 'обход прервался на первом каталоге');
check('удаления не просили', $recorder->deleted === []);
check('предупреждений нет', $warnings === [], implode('; ', $warnings));

echo PHP_EOL, '--- Случай 5: битая ссылка (цели ещё нет) ---', PHP_EOL;

// Ради этого случая порядок проверок в резолвере и важен, и ломается он не так,
// как можно подумать. У битой ссылки `file_exists()` отвечает «нет», но
// `symlink()` на таком пути НЕ падает: он идёт по ссылке и создаёт новую по её
// цели, отвечая успехом (проверено на PHP 8.3.6). То есть прежний код оставлял
// путь битым, плодил ссылку внутри установленного компонента - саму на себя - и
// писал в лог «Symlink restored». Предупреждения, по которому это можно было бы
// заметить, нет вовсе.
run(['core' => 'broken', 'assets' => 'missing']);
$core = state('core');
$stray = $stand . '/core/components/pbauth-gone';

check('ссылка осталась ссылкой', $core['is_link']);
check('цель не подменена', $core['target'] === $stand . '/core/components/pbauth-gone/');
check('лишней ссылки по цели не появилось', !is_link($stray) && !is_dir($stray), 'ссылка уехала в установленный компонент');
// Про `core`, а не про весь лог: `assets` в этом же прогоне ссылку получает
// честно, и строка о нём в логе быть обязана.
$restored = array_filter(
    $transport->xpdo->logged,
    static fn(string $line): bool => str_contains($line, 'Symlink restored')
        && str_contains($line, '/Extras/pbAuth/core/components/pbauth')
);

check('о восстановлении core не отчитались', $restored === [], 'в логе успех, которого не было');
check('удаления не просили', $recorder->deleted === []);

echo PHP_EOL, '--- Случай 6: флаг выключен - резолвер не делает ничего ---', PHP_EOL;

run(['core' => 'dir', 'assets' => 'missing'], false);
$core = state('core');
$assets = state('assets');

check('рабочее дерево цело', $core['marker']);
check('ссылок не появилось', !$core['is_link'] && !$assets['exists'], 'выключенный флаг ставит ссылки');
check('удаления не просили', $recorder->deleted === []);
check('в лог не писали', $transport->xpdo->logged === [], 'выключенный резолвер шумит');

restore_error_handler();
rmrf($stand);

echo PHP_EOL, $fail === 0
    ? 'Все проверки прошли'
    : "Провалов: {$fail}", PHP_EOL;

exit($fail === 0 ? 0 : 1);
