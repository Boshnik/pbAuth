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
        'forgot_password' => [
            'honeypot' => 'empty|exclude',
            'email' => 'required|email|exists:user_attributes,email',
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
        Dispatcher::AFTER_IMPERSONATE => [],
    ],
];
