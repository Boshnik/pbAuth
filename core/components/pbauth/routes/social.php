<?php

use Boshnik\PageBlocks\Facades\Route;
use Boshnik\PbAuth\Support\Controllers;

// Без middleware 'guest': эти же адреса используются для привязки сети к уже
// открытому аккаунту, и контроллер сам разбирает, вход это или привязка.
Route::get('/auth/{provider}', Controllers::action('social', 'redirect'))
    ->where(['provider' => '[a-z0-9_]+'])
    ->name('socialRedirect');

Route::get('/auth/{provider}/callback', Controllers::action('social', 'callback'))
    ->where(['provider' => '[a-z0-9_]+'])
    ->name('socialCallback');

// Telegram возвращает данные тем же способом, что и остальные, но без метки
// состояния — подлинность подтверждает подпись самих данных.
Route::post('/auth/{provider}/callback', Controllers::action('social', 'callback'))
    ->where(['provider' => '[a-z0-9_]+'])
    ->name('socialCallbackPost');

Route::middleware('guest')->group(function () {
    Route::get('/auth-email', Controllers::action('social', 'emailForm'))->name('pageSocialEmail');
    Route::post('/auth-email', Controllers::action('social', 'saveEmail'))->name('socialEmail');
});

Route::middleware('auth')->group(function () {
    Route::post('/profile/social/{provider}/unlink', Controllers::action('social', 'unlink'))
        ->where(['provider' => '[a-z0-9_]+'])
        ->name('socialUnlink');
});
