<?php

use Boshnik\PageBlocks\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', 'Auth\LoginController@show')->name('pageLogin');
    Route::post('/login', 'Auth\LoginController@login')->name('login');

    Route::get('/register', 'Auth\RegisterController@show')->name('pageRegister');
    Route::post('/register', 'Auth\RegisterController@register')->name('register');

    Route::get('/forgot-password', 'Auth\ForgotPasswordController@show')->name('pageForgotPassword');
    Route::post('/forgot-password', 'Auth\ForgotPasswordController@forgotPassword')->name('forgotPassword');

    Route::get('/reset-password/{token}', 'Auth\ResetPasswordController@show')->name('pageResetPassword');
    Route::post('/reset-password', 'Auth\ResetPasswordController@resetPassword')->name('resetPassword');
});

Route::middleware('auth')->group(function () {
    Route::get('/confirm-password', 'Auth\ConfirmPasswordController@show')->name('pageConfirmPassword');
    Route::post('/confirm-password', 'Auth\ConfirmPasswordController@confirmPassword')->name('confirmPassword');

    Route::get('/profile', 'Auth\ProfileController@show')->name('pageProfile');
    Route::post('/profile', 'Auth\ProfileController@updateProfile')->name('updateProfile');

    Route::get('/profile/password', 'Auth\ChangePasswordController@show')->name('pageChangePassword');
    Route::post('/profile/password', 'Auth\ChangePasswordController@changePassword')->name('changePassword');

    Route::get('/logout', 'Auth\LoginController@logout')->name('logout');
});

Route::get('/verify-email/{token}', 'Auth\AuthController@verifyEmail')->name('verifyEmail');