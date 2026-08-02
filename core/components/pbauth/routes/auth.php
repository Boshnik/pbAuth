<?php

use Boshnik\PageBlocks\Facades\Route;
use Boshnik\PbAuth\Support\Controllers;

// Пара [класс, метод], а не строка 'Auth\LoginController@show': строковую форму
// роутер разворачивает в PageBlocks\App\Http\Controllers\, то есть только в
// site-owned слой. Класс берётся из конфига, поэтому сайт может подставить свой
// наследник, не трогая эти роуты.

Route::middleware('guest')->group(function () {
    Route::get('/login', Controllers::action('login', 'show'))->name('pageLogin');
    Route::post('/login', Controllers::action('login', 'login'))->name('login');

    Route::get('/register', Controllers::action('register', 'show'))->name('pageRegister');
    Route::post('/register', Controllers::action('register', 'register'))->name('register');

    Route::get('/forgot-password', Controllers::action('forgot_password', 'show'))->name('pageForgotPassword');
    Route::post('/forgot-password', Controllers::action('forgot_password', 'forgotPassword'))->name('forgotPassword');

    Route::get('/reset-password/{token}', Controllers::action('reset_password', 'show'))->name('pageResetPassword');
    Route::post('/reset-password', Controllers::action('reset_password', 'resetPassword'))->name('resetPassword');
});

Route::middleware('auth')->group(function () {
    Route::get('/confirm-password', Controllers::action('confirm_password', 'show'))->name('pageConfirmPassword');
    Route::post('/confirm-password', Controllers::action('confirm_password', 'confirmPassword'))->name('confirmPassword');

    Route::get('/profile', Controllers::action('profile', 'show'))->name('pageProfile');
    Route::post('/profile', Controllers::action('profile', 'updateProfile'))->name('updateProfile');

    Route::get('/profile/password', Controllers::action('change_password', 'show'))->name('pageChangePassword');
    Route::post('/profile/password', Controllers::action('change_password', 'changePassword'))->name('changePassword');

    Route::get('/logout', Controllers::action('login', 'logout'))->name('logout');
});

Route::get('/verify-email/{token}', Controllers::action('auth', 'verifyEmail'))->name('verifyEmail');
