<?php

use Boshnik\PageBlocks\Facades\Route;
use Boshnik\PbAuth\Support\Controllers;

Route::middleware('guest')->group(function () {
    Route::get('/login', Controllers::action('login', 'show'))->name('pageLogin');
    Route::post('/login', Controllers::action('login', 'login'))->name('login');

    Route::get('/register', Controllers::action('register', 'show'))->name('pageRegister');
    Route::post('/register', Controllers::action('register', 'register'))->name('register');

    Route::get('/forgot-password', Controllers::action('forgot_password', 'show'))->name('pageForgotPassword');
    Route::post('/forgot-password', Controllers::action('forgot_password', 'forgotPassword'))->name('forgotPassword');

    Route::get('/resend-verification', Controllers::action('resend_verification', 'show'))->name('pageResendVerification');
    Route::post('/resend-verification', Controllers::action('resend_verification', 'resend'))->name('resendVerification');

    Route::get('/two-factor', Controllers::action('two_factor', 'show'))->name('pageTwoFactorChallenge');
    Route::post('/two-factor', Controllers::action('two_factor', 'verify'))->name('twoFactorChallenge');

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

    Route::get('/profile/two-factor', Controllers::action('two_factor', 'settings'))->name('pageTwoFactorSettings');
    Route::post('/profile/two-factor', Controllers::action('two_factor', 'enable'))->name('twoFactorEnable');
    Route::post('/profile/two-factor/disable', Controllers::action('two_factor', 'disable'))->name('twoFactorDisable');
    Route::post('/profile/two-factor/backup-codes', Controllers::action('two_factor', 'backupCodes'))->name('twoFactorBackupCodes');

    Route::get('/logout', Controllers::action('login', 'logout'))->name('logout');
});

Route::get('/verify-email/{token}', Controllers::action('auth', 'verifyEmail'))->name('verifyEmail');
