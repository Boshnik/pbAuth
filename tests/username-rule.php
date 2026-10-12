<?php
/**
 * Знаки разметки в имени пользователя.
 *
 * Правило `profile` не ограничивало символы вовсе - `required|unique`, - при том
 * что `register` рядом строгий (`alpha_dash:ascii`). То есть зарегистрироваться
 * с `<img>` в имени было нельзя, а **переименоваться** - можно, и дальше имя
 * ехало в разметку.
 *
 * XSS закрыт не здесь, а экранированием в шаблонах (см. templates-escaping.php).
 * Это второй рубеж, и нужен он для печати **за пределами** pbAuth: своего
 * шаблона сайта, письма, окна менеджера, - где `|escape` никто не обещал.
 *
 * Запрет точечный - пять знаков разметки, а не белый список, - и это решение.
 * Правило профиля применяется и к именам, завёденным не через pbAuth: админом
 * в менеджере MODX, переносом, входом через соцсеть (`SocialController` кладёт
 * в профиль никнейм из сети). Белый список вида `alpha_dash:ascii` отрезал бы
 * их владельцам возможность сохранить профиль вообще, а кириллическое имя на
 * русском сайте - обычное дело.
 *
 * Чем это оплачено, прямо: вместе со знаками разметки отсекается и апостроф,
 * то есть `O'Brien` и `Ім'я` именем пользователя быть не смогут. Для `username`
 * это приемлемо - его набирают латиницей и без пунктуации, - но для `fullname`
 * такой запрет не годится, и там его намеренно нет.
 *
 *   php tests/username-rule.php
 */

$defaults = __DIR__ . '/../core/components/pbauth/config/defaults.php';

$fail = 0;

function check(string $name, bool $ok, string $detail = ''): void
{
    global $fail;

    // Не printf: %-52s считает байты, и кириллица разъезжается по столбцам.
    $pad = str_repeat(' ', max(1, 56 - mb_strlen($name)));

    echo $name, $pad, $ok ? 'ok' : 'ПРОВАЛ' . ($detail !== '' ? " — {$detail}" : ''), PHP_EOL;

    if (!$ok) {
        $fail++;
    }
}

/**
 * Правило из `defaults.php` токенизатором, а не регулярным выражением.
 *
 * Выборка регуляркой на этом файле уже обожглась: строка правила содержит
 * `\'`, и наивный шаблон обрывается на нём, после чего тест проверяет обрубок
 * и бодро всё подтверждает. `token_get_all` разбирает экранирование сам.
 */
function profileUsernameRule(string $file): ?string
{
    foreach (token_get_all((string) file_get_contents($file)) as $token) {
        if (!is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING) {
            continue;
        }

        if (str_starts_with($token[1], "'required|regex:")) {
            return stripcslashes(trim($token[1], "'"));
        }
    }

    return null;
}

$rule = profileUsernameRule($defaults);

echo '--- Правило на месте ---', PHP_EOL;

check('правило username у профиля найдено', $rule !== null, 'в defaults.php нет regex у profile.username');

if ($rule === null) {
    echo PHP_EOL, "Провалов: {$fail}", PHP_EOL;
    exit(1);
}

echo '  ', $rule, PHP_EOL, PHP_EOL;

// Разбор повторяет Validator PageBlocks: explode('|'), затем explode(':', 2) и
// explode(','). Любой из этих знаков внутри шаблона развалил бы правило на
// куски - проверяется, что этого не случилось.
echo '--- Правило переживает разбор валидатора ---', PHP_EOL;

$parts = explode('|', strtr($rule, [':user_id' => '42']));

check('разбор по `|` дал три правила', count($parts) === 3, implode(' / ', $parts));

$pattern = null;
$unique = null;

foreach ($parts as $part) {
    [$name, $rest] = array_pad(explode(':', $part, 2), 2, null);

    if ($name === 'regex') {
        $pattern = explode(',', (string) $rest)[0];
    }

    if ($name === 'unique') {
        $unique = explode(',', (string) $rest);
    }
}

check('шаблон достался целиком', $pattern === '^[^<>"\'&]+$', (string) $pattern);

// Если бы шаблон содержал запятую, `unique` получил бы её хвост и перестал
// исключать самого себя - человек не смог бы сохранить профиль, не меняя имени.
check(
    'unique по-прежнему исключает себя',
    $unique === ['users', 'username', '42'],
    implode(' / ', (array) $unique)
);

echo PHP_EOL, '--- Что проходит и что отсекается ---', PHP_EOL;

$full = $pattern[0] === '/' ? $pattern : '/' . $pattern . '/';

$cases = [
    // Проходит: обычные имена, включая не-латиницу. Белый список их бы срезал.
    'ivan' => true,
    'user_42' => true,
    'jean-luc.p' => true,
    'Иван Петров' => true,
    'Ярослав' => true,
    '日本語' => true,
    'user+tag' => true,
    'a.b-c_d' => true,

    // Отсекается: ровно пять знаков разметки.
    'a<b' => false,
    'a>b' => false,
    'a"b' => false,
    "a'b" => false,
    'a&b' => false,

    // Нагрузки, которыми это и всплыло.
    '" autofocus onfocus=alert(document.domain) x="' => false,
    '<img src=x onerror=alert(1)>' => false,
    '</script><script>alert(1)</script>' => false,

    // Цена запрета, названная вслух: апостроф в имени больше не пройдёт.
    "O'Brien" => false,
    'Ім\'я' => false,
];

foreach ($cases as $value => $allowed) {
    $got = (bool) preg_match($full, (string) $value);
    $label = $value === '' ? '(пусто)' : (string) $value;

    check(
        ($allowed ? 'проходит: ' : 'отказ:    ') . $label,
        $got === $allowed,
        $got ? 'пропущено, а не должно' : 'отказано, а должно проходить'
    );
}

echo PHP_EOL, '--- Вход и регистрация не задеты ---', PHP_EOL;

$source = (string) file_get_contents($defaults);

// Входу такое правило ставить нельзя: вместо «неверный пароль» человек получил
// бы отказ валидации, а владельцы уже завёденных имён со знаками разметки не
// смогли бы войти вовсе, чтобы имя исправить.
check(
    'у login ограничения символов нет',
    (bool) preg_match("~'login' => \[.*?'username' => 'required\|string',~s", $source),
    'правило входа изменено - проверьте, не заблокирован ли вход'
);

// Регистрация строже профиля и такой: `alpha_dash:ascii` уже не пускает
// ничего, кроме латиницы, цифр, дефиса и подчёркивания.
check(
    'у register остался alpha_dash:ascii',
    str_contains($source, 'alpha_dash:ascii'),
    'регистрация ослабла - профиль мягче её, и это неверно'
);

// Сообщение об отказе - своё: общее у валидатора говорит «неверный формат», и
// набравший `Иван & Co` не узнает, что мешает амперсанд.
$controller = (string) file_get_contents(
    __DIR__ . '/../core/components/pbauth/src/Http/Controllers/Auth/ProfileController.php'
);

check(
    'отказ объясняет, какие знаки нельзя',
    str_contains($controller, "'username.regex' => lang('auth.username_forbidden_chars')"),
    'своё сообщение снято - человек увидит «неверный формат»'
);

foreach (['ru', 'en', 'de', 'uk'] as $language) {
    $strings = include __DIR__ . "/../core/components/pbauth/lang/{$language}/auth.php";

    check(
        "сообщение переведено: {$language}",
        !empty($strings['username_forbidden_chars']),
        'ключа нет - наружу уедет само имя ключа'
    );
}

echo PHP_EOL, $fail === 0
    ? 'Все проверки прошли'
    : "Провалов: {$fail}", PHP_EOL;

exit($fail === 0 ? 0 : 1);
