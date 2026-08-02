<?php

use Boshnik\PageBlocks\Facades\Route;
use Boshnik\PbAuth\Support\Controllers;

// Без middleware 'auth': на сайте посетитель может быть и анонимом. Права
// проверяет сам контроллер — ему нужна активная сессия менеджера с флагом sudo.
Route::get('/impersonate/{id}', Controllers::action('impersonate', 'impersonate'))
    ->where(['id' => '[0-9]+'])
    ->name('impersonate');
