<?php
/**
 * Путь к аватару ставит сервер, а не присланное.
 *
 * Было так: правило `'photo' => 'nullable|string'`, а форма возвращала текущий
 * путь скрытым полем, чтобы «Удалить фото» могло его обнулить. Значит путь
 * можно было **прислать**: `fromArray($validated)` писал в профиль любую
 * строку, и она уезжала в `src` картинки - в шапке, в профиле, а на сайтах с
 * форумом ещё и рядом с чужими сообщениями и в обработчик превью. Загрузка
 * перезаписывала значение только когда файл действительно пришёл, то есть
 * запрос без файла проходил насквозь.
 *
 * Экранирование это не закрывало и не могло: из атрибута такое значение не
 * выходит, но сама картинка остаётся выбранной тем, кто отправил запрос.
 *
 * Стало так: форма присылает **намерение** (`delete_photo`), а путь ставит
 * контроллер - из `store()` или пустым при удалении. Третий случай - не
 * трогать: ключа в `$validated` нет, и сохранённое остаётся как есть.
 *
 * Держится это на том, что `Validator::validated()` собирает результат обходом
 * **правил**, а не присланного. Поэтому поле без правила в `$validated` не
 * попадает вовсе - и проверка ниже сторожит именно отсутствие правила.
 *
 * Проверки текстовые, и это осознанно: поднять `updateProfile()` без базы
 * нельзя - он читает `$modx->user`, профиль и хранилище файлов. Поэтому
 * сторожатся три утверждения о коде, каждое из которых по отдельности
 * возвращает дыру.
 *
 *   php tests/profile-photo.php
 */

$root = __DIR__ . '/../core/components/pbauth/';

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

/** Блок правил одного действия из `defaults.php`, как текст. */
function rulesBlock(string $source, string $action): string
{
    if (!preg_match("~'{$action}' => \[(.*?)\n        \],~s", $source, $m)) {
        return '';
    }

    return $m[1];
}

$defaults = (string) file_get_contents($root . 'config/defaults.php');
$profile = rulesBlock($defaults, 'profile');

echo '--- Правила профиля ---', PHP_EOL;

check('блок правил profile найден', $profile !== '', 'разбор defaults.php сломался');

// Главное утверждение. Правило вернёт `photo` в `$validated`, а оттуда
// `fromArray()` запишет присланное в профиль.
check(
    "у profile нет правила 'photo'",
    !preg_match("~'photo'\s*=>~", $profile),
    "правило вернулось - присланный путь снова доедет до профиля"
);

check(
    "'delete_photo' объявлен и исключён из \$validated",
    (bool) preg_match("~'delete_photo' => '[^']*\bexclude\b[^']*'~", $profile),
    'без exclude отметка уедет в fromArray() как поле профиля'
);

// Загрузка остаётся проверяемой: тип, размер и то, что это картинка.
check(
    "'newphoto' по-прежнему проверяется как картинка",
    (bool) preg_match("~'newphoto' => '[^']*\bimage\b[^']*'~", $profile),
    'проверка загружаемого файла ослабла'
);

echo PHP_EOL, '--- Контроллер ---', PHP_EOL;

$controller = (string) file_get_contents($root . 'src/Http/Controllers/Auth/ProfileController.php');

// `photo` присваивается только в двух ветвях, и обе решает сервер.
preg_match_all("~\\\$validated\['photo'\]\s*=\s*([^;]+);~", $controller, $assigns);
$values = array_map('trim', $assigns[1] ?? []);

check(
    "\$validated['photo'] присваивается дважды",
    count($values) === 2,
    'присваиваний ' . count($values) . ': ' . implode(' | ', $values)
);

check(
    'первое присваивание - из сохранённого файла',
    isset($values[0]) && str_contains($values[0], "\$photo['url']"),
    (string) ($values[0] ?? '')
);

check(
    'второе - пустая строка при удалении',
    isset($values[1]) && $values[1] === "''",
    (string) ($values[1] ?? '')
);

// Ни одна ветвь не читает присланный `photo`.
check(
    'контроллер не читает присланный photo',
    !preg_match("~(input|get|post)\(\s*'photo'~", $controller),
    'путь снова берётся из запроса'
);

check(
    'удаление включается отметкой delete_photo',
    (bool) preg_match("~input\(\s*'delete_photo'~", $controller),
    'отметка не читается - кнопка удаления ничего не делает'
);

echo PHP_EOL, '--- Форма ---', PHP_EOL;

$form = (string) file_get_contents($root . 'elements/auth/chunks/form.profile.tpl');

check(
    'форма не присылает поле photo',
    !preg_match('~name="photo"~', $form),
    'скрытое поле с путём вернулось в разметку'
);

check(
    'форма присылает delete_photo',
    (bool) preg_match('~name="delete_photo"~', $form),
    'отметки нет - удалить картинку нечем'
);

// Кнопка и контроллер должны говорить об одном поле: разойдутся - удаление
// молча перестанет работать, и заметят это не тестом, а жалобой.
check(
    'кнопка удаления поднимает delete_photo',
    (bool) preg_match('~\[name=delete_photo\][^"]*value\s*=\s*.1.~', $form),
    'кнопка целится в другое поле'
);

check(
    'кнопка больше не трогает поле photo',
    !preg_match('~\[name=photo\]~', $form),
    'осталась правка несуществующего поля'
);

echo PHP_EOL, $fail === 0
    ? 'Все проверки прошли'
    : "Провалов: {$fail}", PHP_EOL;

exit($fail === 0 ? 0 : 1);
