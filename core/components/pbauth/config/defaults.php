<?php
/**
 * Поставочные настройки pbAuth.
 *
 * Не редактировать: файл принадлежит компоненту и перезаписывается при
 * обновлении. Всё, что нужно изменить сайту, кладётся в `core/App/config/pbauth.php`
 * — образец в `docs/pbauth.config.example.php`.
 */

use Boshnik\PbAuth\Events\Dispatcher;

return [
    // Шаблоны-обёртки страниц. Раскладываются в App/elements/ при установке и
    // с тех пор принадлежат сайту, но путь до них сайт может и переназначить.
    'views' => [
        'auth' => 'file:auth/templates/auth',
        'profile' => 'file:auth/templates/profile',
    ],

    // Чанк формы, который подставляется в шаблон. null — не передавать
    // переменную `form` вовсе: шаблон сайта рисует форму сам.
    'forms' => [
        'login' => 'form.login',
        'register' => 'form.register',
        'forgot_password' => 'form.forgotPassword',
        'reset_password' => 'form.resetPassword',
        'change_password' => 'form.changePassword',
        'confirm_password' => 'form.confirmPassword',
        'resend_verification' => 'form.resendVerification',
        'two_factor_challenge' => 'form.twoFactorChallenge',
        'two_factor' => 'form.twoFactor',
        'profile' => 'form.profile',
    ],

    // Правила валидации по действиям. `:user_id` и `:profile_id` подставляются
    // на лету — без них правило unique не смогло бы исключить самого себя.
    'rules' => [
        'login' => [
            'honeypot' => 'empty',
            'username' => 'required|string',
            'password' => 'required|string|min:8',
        ],
        'register' => [
            'honeypot' => 'empty|exclude',
            'username' => 'required|alpha_dash:ascii|min:3|max:30|unique:users',
            'email' => 'required|email|unique:user_attributes',
            'password' => 'required|string|min:8|confirmed',
        ],
        // Без exists: форма не должна отвечать по-разному на известный и
        // неизвестный адрес — иначе по ней можно перебирать почты.
        'forgot_password' => [
            'honeypot' => 'empty|exclude',
            'email' => 'required|email',
        ],
        'resend_verification' => [
            'honeypot' => 'empty|exclude',
            'email' => 'required|email',
        ],
        'reset_password' => [
            'honeypot' => 'empty|exclude',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ],
        'change_password' => [
            'old_password' => 'required|string|min:8',
            'password' => 'required|string|min:8|confirmed',
        ],
        'confirm_password' => [
            'honeypot' => 'empty',
            'password' => 'required|string|min:8',
        ],
        'two_factor_challenge' => [
            'code' => 'required|string|min:6|max:12',
        ],
        'two_factor_enable' => [
            'code' => 'required|string|min:6|max:6',
        ],
        'two_factor_disable' => [
            'password' => 'required|string',
        ],
        'profile' => [
            'username' => 'required|unique:users,username,:user_id',
            'email' => 'required|email|unique:user_attributes,email,:profile_id',
            'fullname' => 'nullable|string',
            'phone' => 'nullable|string',
            'photo' => 'nullable|string',
            'newphoto' => 'nullable|file|image|mimes:image/jpg,image/jpeg,image/png|max:2048|exclude',
        ],
    ],

    // Куда уводить после успешного действия.
    'redirects' => [
        'login' => '/',
        'logout' => '/',
        'reset_password' => '/',
        'verify_email' => '/',
        'impersonate' => '/',
    ],

    // «Авторизоваться на сайте» из менеджера: кнопка в гриде пользователей и на
    // странице пользователя. Работает только для менеджеров с флагом sudo.
    'impersonate_enabled' => true,

    // Разрешить ли `?redirect=` в форме входа. Значение всегда приводится к
    // пути внутри сайта, чтобы форма не превратилась в открытый редирект.
    'login_redirect_param' => 'redirect',

    // Группы, куда попадает новый пользователь.
    'user_groups' => [],

    // Каталог, куда складываются аватары. `:user_id` — id пользователя.
    'avatar_path' => 'assets/images/avatars/:user_id',

    // Не больше стольких регистраций с одного адреса в час. 0 — без ограничения.
    'register_ip_limit' => 3,

    // Не больше стольких повторных отправок ссылки на один почтовый адрес в час.
    // 0 — без ограничения.
    'resend_verification_limit' => 3,

    // То же для писем со ссылкой на восстановление пароля.
    'forgot_password_limit' => 3,

    // Двухфакторная проверка. Выключатель убирает раздел из профиля и снимает
    // требование кода при входе — уже подключённые секреты при этом сохраняются.
    'two_factor_enabled' => true,
    // Сколько соседних интервалов по 30 секунд принимать, кроме текущего:
    // часы на телефоне и на сервере всегда немного расходятся.
    'two_factor_window' => 1,
    // Сколько секунд между вводом пароля и вводом кода.
    'two_factor_challenge_ttl' => 300,
    // Попыток ввода кода, после которых ожидание сбрасывается. 0 — без предела.
    'two_factor_attempts' => 5,
    // Сколько резервных кодов выдавать.
    'two_factor_backup_codes' => 8,
    // Чьё имя показывает приложение-аутентификатор. Пусто — название сайта.
    'two_factor_issuer' => '',

    // Подмена контроллера: класс сайта наследует поставочный и переопределяет
    // нужные методы, а роуты компонента начинают вести в него.
    'controllers' => [
        'auth' => \Boshnik\PbAuth\Http\Controllers\Auth\AuthController::class,
        'login' => \Boshnik\PbAuth\Http\Controllers\Auth\LoginController::class,
        'register' => \Boshnik\PbAuth\Http\Controllers\Auth\RegisterController::class,
        'profile' => \Boshnik\PbAuth\Http\Controllers\Auth\ProfileController::class,
        'forgot_password' => \Boshnik\PbAuth\Http\Controllers\Auth\ForgotPasswordController::class,
        'reset_password' => \Boshnik\PbAuth\Http\Controllers\Auth\ResetPasswordController::class,
        'change_password' => \Boshnik\PbAuth\Http\Controllers\Auth\ChangePasswordController::class,
        'confirm_password' => \Boshnik\PbAuth\Http\Controllers\Auth\ConfirmPasswordController::class,
        'resend_verification' => \Boshnik\PbAuth\Http\Controllers\Auth\ResendVerificationController::class,
        'two_factor' => \Boshnik\PbAuth\Http\Controllers\Auth\TwoFactorController::class,
        'impersonate' => \Boshnik\PbAuth\Http\Controllers\Auth\ImpersonateController::class,
    ],

    // Слушатели событий. Ключи — константы Dispatcher.
    'listeners' => [
        Dispatcher::USER_SAVING => [],
        Dispatcher::AFTER_REGISTER => [],
        Dispatcher::AFTER_LOGIN => [],
        Dispatcher::AFTER_LOGOUT => [],
        Dispatcher::AFTER_PROFILE_UPDATE => [],
        Dispatcher::AFTER_VERIFY_EMAIL => [],
        Dispatcher::AFTER_RESET_PASSWORD => [],
        Dispatcher::AFTER_CHANGE_PASSWORD => [],
        Dispatcher::AFTER_RESEND_VERIFICATION => [],
        Dispatcher::TWO_FACTOR_ENABLED => [],
        Dispatcher::TWO_FACTOR_DISABLED => [],
        Dispatcher::AFTER_IMPERSONATE => [],
    ],
];
