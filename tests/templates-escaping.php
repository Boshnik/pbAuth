<?php
/**
 * Экранирование вывода в шаблонах pbAuth.
 *
 * До 12.10.2026 `|escape` не стоял ни в одном из 22 шаблонов, а автоэкранирования
 * у Fenom нет: `View::init()` PageBlocks опцию `auto_escape` не передаёт. Печаталось
 * сырым всё - `username`, `fullname`, почта, телефон, адрес аватара, - причём
 * `old_input` это `request()->all()`, то есть присланное целиком
 * (`Response::with('old_input', request()->all())` в PageBlocks).
 *
 * Само по себе это выглядело как self-XSS: свой профиль видит только его хозяин.
 * Опасным это делает `/impersonate/{id}`: менеджер с флагом `sudo` входит под
 * любым пользователем, а менеджер и фронт **делят одну PHP-сессию** - так и
 * написано в `ImpersonateController`. То есть имя, набранное обычным
 * пользователем, печатается в браузере администратора, у которого в той же
 * сессии лежит токен mgr-контекста. Это уже не self-XSS, а повышение прав, и
 * `username` при этом проверяется без ограничения символов.
 *
 * Правило здесь **сплошное**, и это главное отличие от pbShop: в шаблонах pbAuth
 * нет ни одной намеренной разметки - ни готового HTML из PHP, ни HTML в
 * лексиконе (проверено обходом `lang` всех четырёх языков: ни одного тега).
 * Поэтому экранируется каждая печать, без списка исключений, и новое поле
 * попадает под проверку само, без правки теста. В pbShop так нельзя - там
 * `layout.tpl` печатает `{$content}` телом страницы, - и список приходится
 * держать вручную.
 *
 *   php tests/templates-escaping.php
 */

$fenomSrc = '/home/boshnik/Projects/Femom/src/';
$dir = __DIR__ . '/../core/components/pbauth/elements/';

$fail = 0;

/**
 * Пользователь для сборки профиля: `modUser` без базы не поднять, а шаблон
 * читает `username`, `Profile` и зовёт `getGravatar()`.
 *
 * Имя и почта несут нагрузку намеренно - проверяется, что они уедут в разметку
 * экранированными. Адрес аватара безобидный: иначе нельзя отличить
 * «нагрузку экранировали» от «нагрузка пришла из второго места».
 */
class ProfileUserStub
{
    public object $Profile;

    public function __construct(public string $username)
    {
        $this->Profile = (object) ['photo' => '', 'email' => $username];
    }

    public function getGravatar(): string
    {
        return '/assets/gravatar.png';
    }
}

function check(string $name, bool $ok, string $detail = ''): void
{
    global $fail;

    // Не printf: %-58s считает байты, и кириллица разъезжается по столбцам.
    $pad = str_repeat(' ', max(1, 62 - mb_strlen($name)));

    echo $name, $pad, $ok ? 'ok' : 'ПРОВАЛ' . ($detail !== '' ? " — {$detail}" : ''), PHP_EOL;

    if (!$ok) {
        $fail++;
    }
}

/**
 * Печати шаблона: [номер строки, выражение].
 *
 * Комментарии вырезаются с сохранением переводов строк - иначе номера в отчёте
 * съезжают. Печать начинается с `$` или `(`; `{if ...}`, `{foreach ...}`,
 * `{lang ...}`, `{route ...}`, `{include ...}` - это теги, а не вывод.
 */
function prints(string $body): array
{
    $body = preg_replace_callback(
        '~\{\*.*?\*\}~us',
        static fn(array $m): string => str_repeat("\n", substr_count($m[0], "\n")),
        $body
    );

    $keywords = '~^(if|elseif|else|/|foreach|for|set|include|extends|block|lang|route|csrf|auth|guest|admin|switch|case)\b|^/~';
    $found = [];

    foreach (explode("\n", (string) $body) as $number => $line) {
        if (!preg_match_all('~\{([^{}]*(?:\([^()]*\)[^{}]*)*)\}~u', $line, $matches)) {
            continue;
        }

        foreach ($matches[1] as $tag) {
            $tag = trim($tag);

            if ($tag === '' || !preg_match('~^[\$(]~u', $tag) || preg_match($keywords, $tag)) {
                continue;
            }

            $found[] = [$number + 1, $tag];
        }
    }

    return $found;
}

$templates = [];
$walk = static function (string $path) use (&$walk, &$templates): void {
    foreach (scandir($path) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $full = $path . '/' . $entry;

        if (is_dir($full)) {
            $walk($full);
        } elseif (str_ends_with($entry, '.tpl')) {
            $templates[] = $full;
        }
    }
};
$walk(rtrim($dir, '/'));
sort($templates);

echo '--- Движок сам не экранирует ---', PHP_EOL;

if (!file_exists($fenomSrc . 'pbFenom.php')) {
    echo 'Fenom не найден (' . $fenomSrc . ") - замер и сборка пропущены\n";
    $fenom = null;
} else {
    require $fenomSrc . 'pbFenom.php';

    spl_autoload_register(static function (string $class) use ($fenomSrc): void {
        if (!str_starts_with($class, 'pbFenom\\')) {
            return;
        }

        $file = $fenomSrc . str_replace('\\', '/', $class) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    });

    class_alias(pbFenom::class, 'Fenom');

    /** Дописывает `.tpl`, как FileProvider PageBlocks. */
    class AuthProvider extends pbFenom\Provider
    {
        protected function _getTemplatePath(string $tpl): string
        {
            if (!preg_match('/\.(tpl|html)$/', $tpl)) {
                $tpl .= '.tpl';
            }

            return parent::_getTemplatePath($tpl);
        }
    }

    $cache = sys_get_temp_dir() . '/pbauth-esc-' . getmypid() . '/';
    $probe = sys_get_temp_dir() . '/pbauth-probe-' . getmypid() . '/';
    mkdir($cache, 0777, true);
    mkdir($probe, 0777, true);

    // Нагрузка на выход из атрибута: так же, как она работала в поиске pbShop.
    $payload = '" autofocus onfocus=alert(document.domain) x="';
    $breakout = '" autofocus';

    file_put_contents($probe . 'bare.tpl', '<input value="{$q}">');

    // Настройки повторяют View PageBlocks - см. View::init(). `auto_escape`
    // там не передаётся, и именно это здесь и замеряется.
    $options = [
        'auto_reload' => true,
        'force_compile' => true,
        'disable_cache' => false,
        'force_include' => true,
        'force_verify' => true,
    ];

    $probeFenom = Fenom::factory(new AuthProvider($probe), $cache, $options);

    check(
        'без |escape нагрузка выходит из атрибута',
        str_contains($probeFenom->fetch('bare.tpl', ['q' => $payload]), $breakout),
        'движок стал экранировать сам - сплошное правило ниже можно снимать'
    );

    $fenom = Fenom::factory(new AuthProvider($dir), $cache, $options);
    $fenom->addProvider('file', new AuthProvider($dir));

    // Заглушки: регистрирует их View PageBlocks, без них шаблон не собирается.
    foreach (['config', 'trim'] as $modifier) {
        $fenom->addModifier($modifier, static fn($value = '', ...$rest) => (string) $value);
    }

    // `route` и `lang` PageBlocks заводит **дважды** - модификатором
    // (`View::modifiers()`) и тегом (`View::addFunctions()`), - и формы это
    // разные: `{route 'login'}` разбирается тегом, `{route('x', [...])}` -
    // вызовом. Заглушка обязана повторять обе, иначе тест врёт в обе стороны:
    // объявляет поломкой рабочий вызов и пропускает нерабочий тег с запятой.
    foreach (['route', 'lang'] as $modifier) {
        $fenom->addModifier($modifier, static fn($name = '', ...$rest) => (string) $name);
    }

    foreach (['lang', 'route', 'csrf', 'csrf_token'] as $function) {
        $fenom->addFunction($function, static fn() => '');
    }

    foreach (['auth', 'guest', 'admin'] as $block) {
        $fenom->addBlockFunction($block, static fn(array $params, $content) => $content);
    }

    echo PHP_EOL, '--- Шаблоны собираются ---', PHP_EOL;

    foreach ($templates as $path) {
        $name = str_replace($dir, '', $path);

        try {
            $fenom->compile($name);
            check($name, true);
        } catch (\Throwable $e) {
            check($name, false, $e->getMessage());
        }
    }

    echo PHP_EOL, '--- Профиль печатает присланное экранированным ---', PHP_EOL;

    // Проверка не на текст шаблона, а на то, что он печатает. Профиль - то
    // самое место, куда смотрит администратор, вошедший под пользователем.
    $rendered = $fenom->fetch('auth/chunks/form.profile.tpl', [
        'old_input' => [
            'username' => $payload,
            'fullname' => $payload,
            'email' => $payload,
            'phone' => $payload,
        ],
        'up' => (object) ['fullname' => '', 'email' => '', 'phone' => ''],
        'errors' => [],
        'photo' => $payload,
        'avatar' => $payload,
        // Заглушка пользователя, а не stdClass: шаблон зовёт `getGravatar()`
        // и читает `Profile`, и без них падает на рантайме.
        'modx' => (object) ['user' => new ProfileUserStub($payload)],
        'success_message' => '',
        'error_message' => '',
        'social_providers' => [],
        'social_linked' => [],
    ]);

    check(
        'form.profile.tpl не выпускает нагрузку из атрибута',
        !str_contains($rendered, $breakout),
        'присланное печатается сырым в value'
    );

    check(
        'нагрузка в нём экранирована, а не выброшена',
        str_contains($rendered, '&quot; autofocus'),
        'значение потерялось - проверка ничего не доказывает'
    );

    array_map('unlink', glob($cache . '*'));
    array_map('unlink', glob($probe . '*'));
    @rmdir($cache);
    @rmdir($probe);
}

echo PHP_EOL, '--- Каждая печать экранирована (правило сплошное) ---', PHP_EOL;

$totalPrints = 0;

foreach ($templates as $path) {
    $name = str_replace($dir, '', $path);
    $raw = [];

    foreach (prints((string) file_get_contents($path)) as [$number, $tag]) {
        $totalPrints++;

        if (!preg_match('~\|\s*e(scape)?\b~u', $tag)) {
            $raw[] = "{$number}: {$tag}";
        }
    }

    check($name, $raw === [], implode(' | ', $raw));
}

check('печатей найдено', $totalPrints > 100, "их {$totalPrints} - разбор печатей сломался?");

echo PHP_EOL, '--- `?:` целиком в скобках ---', PHP_EOL;

// Отдельная проверка, и не ради порядка. Модификатор у `?:` цепляется только к
// последнему операнду: `{$typed ?: $saved|escape}` экранирует запасное значение
// и печатает присланное **сырым**, при этом выглядит исправленным. Замерено на
// движке. Поэтому выражение с тернарником обязано уехать в скобки целиком:
// `{($typed ?: $saved)|escape}`.
foreach ($templates as $path) {
    $name = str_replace($dir, '', $path);
    $bad = [];

    foreach (prints((string) file_get_contents($path)) as [$number, $tag]) {
        if (!str_contains($tag, '?')) {
            continue;
        }

        // Годится только когда всё выражение завёрнуто: `(...)|escape`.
        if (!preg_match('~^\(.*\)\s*\|\s*e(scape)?\b~u', $tag)) {
            $bad[] = "{$number}: {$tag}";
        }
    }

    check($name, $bad === [], implode(' | ', $bad));
}

echo PHP_EOL, $fail === 0
    ? 'Все проверки прошли'
    : "Провалов: {$fail}", PHP_EOL;

exit($fail === 0 ? 0 : 1);
