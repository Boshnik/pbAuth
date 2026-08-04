<?php

return [
    // Controllers
    'user_confirm_password_success' => 'Ваш пароль подтверждён.',
    'forgot_password_success' => 'Если адрес зарегистрирован и подтверждён, мы отправили ссылку для смены пароля. Проверьте почту и папку «Спам».',
    'update_profile_success' => 'Профиль успешно обновлён.',
    'register_error' => 'Не удалось зарегистрировать пользователя.',
    'register_success' => 'Проверьте электронную почту для подтверждения регистрации.',
    'invalid_token' => 'Недопустимый токен',
    'old_password_error' => 'Старый пароль введён неверно',
    'change_password_success' => 'Пароль успешно изменён',
    'recaptcha_failed' => 'Ошибка проверки reCAPTCHA. Попробуйте еще раз.',
    'register_ip_error' => 'С вашего IP слишком много регистраций. Попробуйте позже.',

    // Title
    'reset_password_title' => 'Сброс пароля',
    'register_title' => 'Регистрация',
    'profile_title' => 'Профиль',
    'login_title' => 'Вход',
    'forgot_password_title' => 'Забыли пароль',
    'confirm_password_title' => 'Подтвердите пароль',
    'change_password_title' => 'Изменить пароль',

    // Emails
    'register_subject' => 'Подтвердите свой аккаунт',
    'reset_password_subject' => 'Сброс пароля',

    // Form
    'forgot_password' => 'Забыли пароль?',
    'remember' => 'Запомнить меня',
    'log_in' => 'Войти',
    'sign_up' => 'Зарегистрироваться',
    'signup_prompt' => 'Нет аккаунта?',
    'login_prompt' => 'Уже есть аккаунт?',
    'return_ro' => 'Или вернитесь к',
    'delete_photo' => 'Удалить фото',

    'field_login' => 'Логин или Email',
    'field_username' => 'Имя',
    'field_fullname' => 'Полное имя',
    'field_password' => 'Пароль',
    'field_new_password' => 'Новый пароль',
    'field_current_password' => 'Текущий пароль',
    'field_confirm_password' => 'Подтвердите пароль',
    'field_email' => 'Email',
    'field_phone' => 'Телефон',

    // form login
    'form_login_title' => 'Вход в аккаунт',
    'form_login_subtitle' => 'Введите логин или email и пароль',
    'form_login_submit' => 'Войти',
    // form register
    'form_register_title' => 'Создать аккаунт',
    'form_register_subtitle' => 'Введите данные для регистрации',
    'form_register_submit' => 'Создать аккаунт',
    // form reset password
    'form_reset_password_title' => 'Сброс пароля',
    'form_reset_password_subtitle' => 'Введите новый пароль',
    'form_reset_password_submit' => 'Сбросить пароль',
    // form forgot password
    'form_forgot_password_title' => 'Восстановление пароля',
    'form_forgot_password_subtitle' => 'Введите email для получения ссылки',
    'form_forgot_password_submit' => 'Отправить ссылку',
    // form confirm password
    'form_confirm_password_title' => 'Подтвердите пароль',
    'form_confirm_password_subtitle' => 'Это защищенная зона. Подтвердите пароль.',
    'form_confirm_password_submit' => 'Подтвердить',
    // form change password
    'form_change_password_title' => 'Обновить пароль',
    'form_change_password_subtitle' => 'Используйте надежный пароль',
    'form_change_password_submit' => 'Сохранить',
    // form profile
    'form_profile_title' => 'Профиль',
    'form_profile_submit' => 'Сохранить',

    // impersonate
    'impersonate_no_manager' => 'Требуется вход в панель управления',
    'impersonate_denied' => 'Недостаточно прав',
    'impersonate_not_found' => 'Пользователь не найден',
    'impersonate_blocked' => 'Пользователь заблокирован',

    // resend verification
    'resend_verification_title' => 'Повторная отправка ссылки',
    'resend_verification_success' => 'Если адрес зарегистрирован и ещё не подтверждён, мы отправили ссылку повторно. Проверьте почту и папку «Спам».',
    'resend_verification_limit_error' => 'Слишком много попыток. Попробуйте через час.',
    'form_resend_verification_title' => 'Ссылка для подтверждения',
    'form_resend_verification_subtitle' => 'Письмо не пришло или потерялось? Укажите адрес, и мы отправим ссылку заново.',
    'form_resend_verification_submit' => 'Отправить ссылку',
    'resend_prompt' => 'Не пришло письмо с подтверждением?',
    'resend_link' => 'Отправить заново',
    'forgot_password_limit_error' => 'Слишком много попыток. Попробуйте через час.',

    // two factor
    'two_factor_title' => 'Подтверждение входа',
    'two_factor_settings_title' => 'Подтверждение входа',
    'field_code' => 'Код из приложения',
    'form_two_factor_title' => 'Остался один шаг',
    'form_two_factor_subtitle' => 'Откройте приложение-аутентификатор и введите код, который оно показывает.',
    'form_two_factor_submit' => 'Подтвердить',
    'two_factor_backup_hint' => 'Потеряли телефон? Введите вместо кода один из резервных.',
    'two_factor_invalid' => 'Код не подошёл. Проверьте время на телефоне и введите следующий.',
    'two_factor_expired' => 'Время ожидания вышло. Войдите заново.',
    'two_factor_too_many' => 'Слишком много неверных кодов. Войдите заново.',
    'two_factor_setup_expired' => 'Время на подключение вышло. Обновите страницу и начните заново.',
    'two_factor_already_enabled' => 'Подтверждение входа уже включено.',
    'two_factor_not_enabled' => 'Подтверждение входа выключено.',
    'two_factor_is_on' => 'Подтверждение входа включено.',
    'two_factor_enabled_success' => 'Подтверждение входа включено. Сохраните резервные коды.',
    'two_factor_disabled_success' => 'Подтверждение входа выключено.',
    'two_factor_codes_regenerated' => 'Выпущены новые резервные коды. Старые больше не работают.',
    'two_factor_codes_title' => 'Резервные коды',
    'two_factor_codes_warning' => 'Сохраните их в надёжном месте. Каждый срабатывает один раз, и показываются они только сейчас — потеряв и телефон, и коды, вы потеряете доступ к аккаунту.',
    'two_factor_codes_left' => 'Осталось резервных кодов',
    'two_factor_setup_intro' => 'Кроме пароля при входе будет спрашиваться код с вашего телефона. Даже украденного пароля станет недостаточно, чтобы войти.',
    'two_factor_step_app' => 'Установите приложение-аутентификатор — Google Authenticator, 1Password, Authy или любое другое.',
    'two_factor_step_secret' => 'Добавьте в него аккаунт: с телефона нажмите на ссылку, с компьютера введите ключ вручную.',
    'two_factor_step_code' => 'Введите код, который показало приложение, — так мы убедимся, что всё работает.',
    'two_factor_open_app' => 'Открыть в приложении',
    'two_factor_enable_submit' => 'Включить',
    'two_factor_disable_title' => 'Выключить',
    'two_factor_disable_hint' => 'Аккаунт останется защищён только паролем. Подтвердите действие текущим паролем.',
    'two_factor_disable_submit' => 'Выключить',
    'two_factor_regenerate_title' => 'Выпустить новые резервные коды',
    'two_factor_regenerate_hint' => 'Старые коды перестанут работать сразу.',
    'two_factor_regenerate_submit' => 'Выпустить',
    'two_factor_link' => 'Подтверждение входа',
];
