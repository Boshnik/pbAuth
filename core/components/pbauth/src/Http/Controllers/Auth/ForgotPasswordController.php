<?php

namespace Boshnik\PbAuth\Http\Controllers\Auth;

use Boshnik\PageBlocks\Http\Request;
use Boshnik\PageBlocks\Support\Mail;
use Boshnik\PbAuth\Support\Config;

/**
 * Восстановление пароля по ссылке из письма.
 *
 * Ответ одинаковый при любом исходе — как и у повторной отправки подтверждения.
 * Форма, которая отвечает по-разному на знакомый и незнакомый адрес, позволяет
 * перебором выяснить, кто зарегистрирован на сайте.
 */
class ForgotPasswordController extends AuthController
{
    public function show()
    {
        return $this->page('auth', 'forgot_password', [
            'title' => lang('auth.forgot_password_title'),
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate(Config::rules('forgot_password'));

        $limit = (int)Config::get('forgot_password_limit', 0);
        $attemptsKey = 'pbauth_forgot_attempts:' . sha1(strtolower($validated['email']));
        $attempts = (int)cache($attemptsKey);

        if ($limit > 0 && $attempts >= $limit) {
            return response()->error(lang('auth.forgot_password_limit_error'));
        }

        if ($limit > 0) {
            cache($attemptsKey, $attempts + 1, 3600);
        }

        if ($user = $this->findResettableUser($validated['email'])) {
            $token = bin2hex(random_bytes(32));
            $expires = strtotime('+1 hour');

            $user->set('remote_key', $token);
            $user->set('remote_data', json_encode(compact('expires')));
            $user->save();

            $homePage = $this->modx->getOption('site_url');
            $validated['username'] = $user->username;
            $validated['link'] = "{$homePage}reset-password/$token";

            Mail::to($validated['email'])
                ->subject(lang('auth.reset_password_subject'))
                ->view('file:auth/chunks/email.resetPassword', $validated)
                ->send();
        }

        return response()->success(lang('auth.forgot_password_success'));
    }

    /**
     * Кому вообще можно менять пароль по ссылке.
     *
     * Неподтверждённый аккаунт пропускаем: войти под ним всё равно нельзя, пока
     * не пройдено подтверждение почты, а `remote_key` у MODX один на оба случая
     * — выданная сейчас ссылка сброса убила бы ссылку подтверждения. Такому
     * пользователю нужна страница повторной отправки, а не эта.
     */
    protected function findResettableUser(string $email)
    {
        $profile = $this->modx->getObject($this->profileClassKey, ['email' => $email]);
        if (!$profile) {
            return null;
        }

        $user = $profile->getOne('User');
        if (!$user || !$user->get('active')) {
            return null;
        }

        return $user;
    }
}
