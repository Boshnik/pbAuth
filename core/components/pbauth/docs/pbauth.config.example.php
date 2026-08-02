<?php
/**
 * Образец. Скопировать в core/App/config/pbauth.php на сайте.
 *
 * Файл принадлежит сайту: pbAuth его только читает и никогда не поставляет,
 * поэтому обновление компонента не может его затереть. Указывать нужно лишь то,
 * что отличается от поставочного — остальное берётся из
 * core/components/pbauth/config/defaults.php.
 */

use Boshnik\PbAuth\Events\Dispatcher;

return [
    // Свои шаблоны-обёртки вместо поставочных.
    'views' => [
        // 'profile' => 'file:templates/profile',
    ],

    // Чанк формы для страницы. null — не передавать `form` вовсе, если шаблон
    // сайта рисует форму сам.
    'forms' => [
        // 'profile' => null,
    ],

    // Поля и правила. Словарь сливается по ключам: неизвестное поле
    // добавляется, известное заменяется, null — убирает поставочное.
    'rules' => [
        // 'register' => [
        //     'phone' => 'nullable|string|unique:user_attributes',
        // ],
        // 'profile' => [
        //     'phone'    => 'required|string',
        //     'country'  => 'required|integer',
        //     'telegram' => 'nullable|string',
        //     'fullname' => null,
        // ],
    ],

    // Куда уводить после успешного действия.
    'redirects' => [
        // 'login' => '/profile',
    ],

    // Группы, в которые попадает новый пользователь.
    'user_groups' => [
        // 'Users',
    ],

    // Подмена контроллера: класс сайта наследует поставочный и переопределяет
    // нужные методы. Роуты компонента начинают вести в него сами.
    'controllers' => [
        // 'register' => \PageBlocks\App\Http\Controllers\Auth\RegisterController::class,
    ],

    // Слушатель — класс с методом handle(array $params, string $event) либо
    // любой callable. Исключение в слушателе логируется и не роняет действие.
    'listeners' => [
        // Вызывается до save() и при регистрации, и при правке профиля.
        // $params: user, profile, validated, action ('register' | 'profile').
        // Сюда сайт кладёт поля, которых нет среди колонок modUserProfile.
        // Dispatcher::USER_SAVING => [
        //     \PageBlocks\App\Events\Auth\StoreExtendedFields::class,
        // ],
        // Dispatcher::AFTER_REGISTER => [],
        // Dispatcher::AFTER_LOGIN => [],
        // Dispatcher::AFTER_PROFILE_UPDATE => [],
    ],
];
