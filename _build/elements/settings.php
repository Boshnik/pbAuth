<?php

return [
    'user_page' => [
        'xtype' => 'textfield',
        'value' => 'users/{id}',
        'area' => 'manager',
    ],
    'recaptcha_service' => [
        'xtype' => 'textfield',
        'value' => 'Google reCAPTCHA',
        'area' => 'recaptcha',
    ],
    'recaptcha_public_key' => [
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'recaptcha',
    ],
    'recaptcha_secret_key' => [
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'recaptcha',
    ],
];
