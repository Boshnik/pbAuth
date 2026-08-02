# pbAuth — как это устроено и как это менять

Авторизация, регистрация и профиль для сайтов на PageBlocks 3.

Главная мысль: **код компонента не копируется на сайт и не правится**. Всё, что
сайту нужно изменить, задаётся снаружи — конфигом, событием или наследником
контроллера. Поэтому исправленный в компоненте баг доезжает до сайта обычным
обновлением, а правки сайта обновление не затирает.

---

## 1. Что где лежит

| Путь | Кому принадлежит | Обновляется? |
|---|---|---|
| `core/components/pbauth/src/` | компоненту | да, целиком |
| `core/components/pbauth/routes/auth.php` | компоненту | да |
| `core/components/pbauth/config/defaults.php` | компоненту | да |
| `core/components/pbauth/lexicon/` | компоненту (строки в менеджере) | да |
| `core/App/config/pbauth.php` | **сайту** | никогда |
| `core/App/elements/auth/` — шаблоны и чанки | **сайту** | только чего нет |
| `core/App/lang/{локаль}/auth.php` | **сайту** | только чего нет |

Правило простое: всё внутри `core/components/pbauth/` — **не трогать**, при
обновлении перезапишется. Всё внутри `core/App/` — ваше, компонент туда только
докладывает недостающее и никогда ничего не перезаписывает.

### Почему шаблоны копируются, а контроллеры нет

Дизайн у каждого сайта свой — тут никакая настройка не помогает, проще отдать
файл и забыть про него. А логика у всех одна и та же с точностью до полей и
редиректов — её выгоднее держать в одном месте и настраивать.

---

## 2. Путь запроса

```
GET /login
  │
  ├─ bootstrap.php компонента: Route::addRoutesPath(core/components/pbauth/routes)
  │
  ├─ routes/auth.php:  Route::get('/login', Controllers::action('login', 'show'))
  │                                          │
  │                                          └─ читает controllers.login из конфига
  │
  ├─ LoginController::show()
  │     └─ $this->page('auth', 'login', ['title' => lang('auth.login_title')])
  │            ├─ views.auth      → 'file:auth/templates/auth'  → core/App/elements/auth/templates/auth.tpl
  │            └─ forms.login     → 'form.login'                → в шаблон приходит $form
  │
  └─ шаблон подключает core/App/elements/auth/chunks/form.login.tpl
```

`file:` — это префикс провайдера шаблонов PageBlocks, он ведёт в
`core/App/elements/`. То есть `file:auth/templates/auth` — это
`core/App/elements/auth/templates/auth.tpl` (расширение `.tpl` дописывается само,
если его нет).

---

## 3. Конфиг сайта

Создайте `core/App/config/pbauth.php`. Образец — `docs/pbauth.config.example.php`.
Писать нужно **только отличия** от `core/components/pbauth/config/defaults.php`,
остальное подставится само.

```php
<?php

use Boshnik\PbAuth\Events\Dispatcher;
use PageBlocks\App\Events\Auth\StoreExtendedFields;

return [
    'views'     => ['profile' => 'file:templates/profile'],
    'forms'     => ['profile' => null],
    'rules'     => ['profile' => ['phone' => 'required|string']],
    'listeners' => [Dispatcher::USER_SAVING => [StoreExtendedFields::class]],
];
```

### Как склеиваются значения

Словари сливаются **по ключам вглубь**, списки заменяются целиком.

```php
// в defaults.php
'rules' => ['register' => ['username' => '...', 'email' => '...', 'password' => '...']]

// у вас
'rules' => ['register' => ['phone' => 'nullable|string', 'email' => 'required|email']]

// в итоге: username (из defaults), email (ваш), password (из defaults), phone (ваш)
```

Чтобы **убрать** поставочное поле, задайте ему `null`:

```php
'rules' => ['profile' => ['fullname' => null]],
```

А вот `user_groups` — список, он заменяется целиком:

```php
'user_groups' => ['Users', 'Customers'],   // ровно эти две группы, не плюс к чему-то
```

### Все ключи

| Ключ | Что делает |
|---|---|
| `views.auth`, `views.profile` | шаблоны-обёртки страниц |
| `forms.*` | чанк формы, который подставится в шаблон. `null` — не передавать `$form` вовсе |
| `rules.*` | поля и правила валидации по действиям |
| `redirects.login` / `logout` / `reset_password` / `verify_email` | куда уводить после успеха |
| `login_redirect_param` | имя GET-параметра для возврата после входа (по умолчанию `redirect`). `''` — выключить |
| `user_groups` | группы, куда попадает новый пользователь |
| `avatar_path` | куда складывать аватары, `:user_id` подставляется |
| `register_ip_limit` | сколько регистраций с одного IP в час, `0` — без ограничения |
| `controllers.*` | подмена класса контроллера |
| `listeners.*` | слушатели событий |

Действия (`*` выше) одни и те же везде: `login`, `register`, `profile`,
`forgot_password`, `reset_password`, `change_password`, `confirm_password`, плюс
`auth` в `controllers` — это базовый контроллер, он обслуживает подтверждение
почты.

---

## 4. Как решать типовые задачи

### Добавить поле в форму регистрации

Три шага, контроллер не трогаем.

**1. Правило в конфиг:**

```php
'rules' => [
    'register' => [
        'phone' => 'required|string|unique:user_attributes',
    ],
],
```

**2. Поле в чанк** `core/App/elements/auth/chunks/form.register.tpl` — рядом с
остальными, по образцу соседей.

**3. Всё.** Если у поля есть колонка в `modx_users` или `modx_user_attributes`
(как у `phone`), оно сохранится само: контроллер отдаёт весь провалидированный
массив в `fromArray()`.

Если колонки нет — нужен слушатель, см. следующий пункт.

> Валидатор `unique:` и `exists:` работают **с именем таблицы**, а не с классом
> MODX: `unique:users`, `exists:user_attributes,email`. Имя без префикса —
> префикс добавится сам.

### Сохранить поле, у которого нет колонки

Такие поля кладут в `extended` профиля. Пишем слушателя `USER_SAVING` — он
срабатывает **до** `save()`, поэтому отдельно сохранять ничего не надо.

`core/App/Events/Auth/StoreExtendedFields.php`:

```php
<?php

namespace PageBlocks\App\Events\Auth;

class StoreExtendedFields
{
    protected const FIELDS = ['telegram', 'viber'];

    public function handle(array $params, string $event): void
    {
        $profile = $params['profile'] ?? null;
        $validated = $params['validated'] ?? [];

        if (!$profile) {
            return;
        }

        $extended = $profile->get('extended') ?: [];
        foreach (static::FIELDS as $field) {
            if (array_key_exists($field, $validated)) {
                $extended[$field] = $validated[$field] ?? '';
            }
        }

        $profile->set('extended', $extended);
    }
}
```

Регистрируем в конфиге:

```php
'listeners' => [
    Dispatcher::USER_SAVING => [StoreExtendedFields::class],
],
```

### Изменить вёрстку

Правьте файлы прямо в `core/App/elements/auth/` — они ваши, обновление их не
тронет. Хотите свой шаблон в другом месте — укажите путь в конфиге:

```php
'views' => ['profile' => 'file:templates/profile'],   // core/App/elements/templates/profile.tpl
```

Если шаблон рисует форму сам и переменная `$form` ему не нужна:

```php
'forms' => ['profile' => null],
```

### Изменить тексты

Строки фронта — `core/App/lang/{ru,en,uk,de}/auth.php`, в шаблонах
`{lang 'auth.login_title'}`, в PHP `lang('auth.login_title')`. Файлы ваши,
правьте на месте. Новый ключ добавляйте **во все четыре локали**.

Не путать с `core/components/pbauth/lexicon/` — это подписи системных настроек в
менеджере MODX, они принадлежат компоненту.

### Повесить свою логику на событие

```php
'listeners' => [
    Dispatcher::AFTER_REGISTER => [
        \PageBlocks\App\Events\Auth\SendWelcomeLetter::class,
        \PageBlocks\App\Events\Auth\NotifyManager::class,
    ],
],
```

Слушатель — класс с методом `handle(array $params, string $event)` либо любой
callable (замыкание, `[$obj, 'method']`). Исключение в слушателе логируется и
**не роняет** действие: пользователь уже создан, ронять запрос из-за неотправленного
письма нельзя. Слушатели одного события выполняются по порядку.

Дополнительно вызывается одноимённое системное событие MODX — это для сторонних
плагинов. Плагину уходят только скаляры (`user_id` вместо объекта). Свою логику
лучше вешать конфигом: плагин после каждого деплоя приходится заново прикреплять
в **System Events**, а конфиг работает сразу.

### Переопределить контроллер целиком

Когда события и конфига мало — наследуйтесь. Свой класс кладётся в App, роуты
компонента начнут вести в него сами.

`core/App/Http/Controllers/Auth/RegisterController.php`:

```php
<?php

namespace PageBlocks\App\Http\Controllers\Auth;

use Boshnik\PageBlocks\Http\Request;

class RegisterController extends \Boshnik\PbAuth\Http\Controllers\Auth\RegisterController
{
    public function register(Request $request)
    {
        if ($this->isBlacklisted($request->ip())) {
            return response()->error('Регистрация с этого адреса закрыта');
        }

        return parent::register($request);
    }
}
```

```php
'controllers' => [
    'register' => \PageBlocks\App\Http\Controllers\Auth\RegisterController::class,
],
```

Переопределять целиком стоит только то, что действительно нужно, — всё
остальное продолжит приходить из компонента вместе с обновлениями. Полезные
защищённые методы базового `AuthController`: `page()` (собрать страницу по
конфигу), `redirectTo()` (взять редирект из конфига), `authenticate($user)`
(залогинить во всех контекстах), `getProcessorError()`.

### Добавить свою страницу в раздел профиля

⚠️ **Только не файлом `core/App/routes/auth.php`.** Пока этот файл существует,
pbAuth считает, что сайт остался на старой схеме, и **не регистрирует свои роуты
вообще** — иначе те же адреса зарегистрировались бы дважды. Назовите файл иначе:

`core/App/routes/profile-extra.php`:

```php
<?php

use Boshnik\PageBlocks\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/profile/settings', 'ProfileSettingsController@show')->name('profileSettings');
});
```

Здесь работает обычная строковая форма `'Контроллер@метод'` — роутер разворачивает
её в `PageBlocks\App\Http\Controllers\`, то есть в ваш App.

> Роуты компонента адресуют контроллеры парой `[класс, метод]` именно потому, что
> строковая форма умеет попасть только в App.

Порядок объявления роутов значения не имеет: конкретный путь всегда выигрывает у
шаблонного (`/profile/settings` победит `/profile/{alias}`), кто бы что раньше ни
объявил.

### Отдать пользователя туда, откуда его завернули

Добавьте в форму входа скрытое поле:

```html
<input type="hidden" name="redirect" value="{$.get.redirect|escape}">
```

Принимается только путь внутри сайта — со схемой или `//host` значение
отбрасывается, чтобы форма входа не стала открытым редиректом. Имя параметра
меняется через `login_redirect_param`, пустая строка выключает механизм.

---

## 5. События

Все константы — в `Boshnik\PbAuth\Events\Dispatcher`.

| Константа | Имя события | Когда | `$params` |
|---|---|---|---|
| `USER_SAVING` | `pbAuthUserSaving` | до `save()` при регистрации **и** при правке профиля | `user`, `profile`, `validated`, `action` (`register`\|`profile`) |
| `AFTER_REGISTER` | `pbAuthAfterRegister` | после создания пользователя, до письма | `user`, `profile`, `validated` |
| `AFTER_LOGIN` | `pbAuthAfterLogin` | после успешного входа | `user` |
| `AFTER_LOGOUT` | `pbAuthAfterLogout` | после выхода | `user` |
| `AFTER_PROFILE_UPDATE` | `pbAuthAfterProfileUpdate` | после сохранения профиля | `user`, `profile`, `validated` |
| `AFTER_VERIFY_EMAIL` | `pbAuthAfterVerifyEmail` | после подтверждения почты | `user` |
| `AFTER_RESET_PASSWORD` | `pbAuthAfterResetPassword` | после сброса пароля | `user` |
| `AFTER_CHANGE_PASSWORD` | `pbAuthAfterChangePassword` | после смены пароля | `user` |

`USER_SAVING` — единственное событие, где изменения объекта имеют смысл:
`$params['user']` и `$params['profile']` ещё не сохранены. В остальных объекты
уже записаны, менять их нужно с явным `save()`.

Событие можно повесить и из кода, минуя конфиг:

```php
Dispatcher::listen(Dispatcher::AFTER_LOGIN, function (array $params) {
    // ...
});
```

---

## 6. Роуты и их имена

Ссылайтесь на страницы по имени — `{route 'pageProfile'}` в шаблоне,
`route('pageProfile')` в PHP. Адрес поменяется — ссылки останутся рабочими.

| Метод | URI | Имя |
|---|---|---|
| GET / POST | `/login` | `pageLogin` / `login` |
| GET / POST | `/register` | `pageRegister` / `register` |
| GET / POST | `/forgot-password` | `pageForgotPassword` / `forgotPassword` |
| GET / POST | `/reset-password/{token}`, `/reset-password` | `pageResetPassword` / `resetPassword` |
| GET / POST | `/confirm-password` | `pageConfirmPassword` / `confirmPassword` |
| GET / POST | `/profile` | `pageProfile` / `updateProfile` |
| GET / POST | `/profile/password` | `pageChangePassword` / `changePassword` |
| GET | `/logout` | `logout` |
| GET | `/verify-email/{token}` | `verifyEmail` |

Гостевые страницы закрыты middleware `guest`, страницы профиля — `auth`.

---

## 7. Системные настройки

Задаются в менеджере MODX, раздел **pbauth**:

| Настройка | Зачем |
|---|---|
| `pbauth_recaptcha_service` | какой антиспам-сервис используется |
| `pbauth_recaptcha_public_key` | Site key, подставляется в шаблон |
| `pbauth_recaptcha_secret_key` | Secret key. **Пока он пуст, reCAPTCHA не проверяется** |

---

## 8. Установка, обновление, удаление

**Установка и обновление ведут себя одинаково**: копируют в `core/App/` только
те файлы, которых там нет. Существующий файл не перезаписывается никогда — раз он
в App, он ваш.

Что именно было положено, компонент записывает в
`core/App/.pbauth-installed.json` — путь и хеш на момент установки.

**Удаление** сносит только те файлы, чей хеш всё ещё совпадает с записанным.
Файл, который вы правили, остаётся. Файл, изменённый после установки, вычёркивается
из манифеста навсегда — он перешёл к сайту.

### Переход со старых версий (до 1.1.0)

До 1.1.0 компонент копировал в `core/App/` ещё и роуты с контроллерами. При
обновлении:

- `App/routes/auth.php` удаляется, **если он побайтно совпадает** с поставочным.
  Правленый остаётся — и тогда pbAuth не подаёт свои роуты, чтобы адреса не
  задвоились. Сайт продолжает работать на старом коде;
- контроллеры в `App/Http/Controllers/Auth/` остаются. Когда перенесёте правки в
  `App/config/pbauth.php` и слушателей, удалите вручную и их, и
  `App/routes/auth.php` — после этого сайт перейдёт на код компонента.

Проверить, на чём вы сейчас: если `core/App/routes/auth.php` существует — на
старой схеме. В лог MODX об этом пишется на каждом запросе (уровень INFO).

---

## 9. Ловушки

**`core/App/routes/auth.php` выключает роуты компонента.** Именно этот путь,
именно это имя. Свои роуты — в файл с другим именем.

**Правки в `core/components/pbauth/` пропадут** при обновлении. Если хочется
поправить компонентный контроллер — значит, не хватает точки расширения: заведите
её (событие или ключ конфига), а не правьте на месте.

**`unique:` и `exists:` принимают имя таблицы, не класс MODX.** `unique:users`,
а не `unique:modUser` — второе молча ничего не находит и пропускает дубли.

**Кэш.** Конфиг читается заново каждый запрос — правку видно сразу. А вот таблица
роутов кэшируется, поэтому после правки роутов чистите кэш MODX.

**Локали.** Новый ключ в `lang/` добавляйте сразу во все четыре — `en`, `ru`,
`uk`, `de`.
