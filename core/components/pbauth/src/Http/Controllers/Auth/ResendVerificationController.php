<?php

namespace Boshnik\PbAuth\Http\Controllers\Auth;

use Boshnik\PageBlocks\Http\Request;
use Boshnik\PbAuth\Events\Dispatcher;
use Boshnik\PbAuth\Support\Config;

/**
 * Повторная отправка ссылки для подтверждения почты.
 *
 * Ответ одинаковый при любом исходе: и когда письмо ушло, и когда такого адреса
 * нет, и когда он давно подтверждён. Иначе форма превратилась бы в способ
 * проверять, зарегистрирован ли адрес на сайте, — перебором.
 */
class ResendVerificationController extends AuthController
{
    public function show()
    {
        return $this->page('auth', 'resend_verification', [
            'title' => lang('auth.resend_verification_title'),
        ]);
    }

    public function resend(Request $request)
    {
        $validated = $request->validate(Config::rules('resend_verification'));

        $limit = (int)Config::get('resend_verification_limit', 0);
        $attemptsKey = 'pbauth_resend_attempts:' . sha1(strtolower($validated['email']));
        $attempts = (int)cache($attemptsKey);

        if ($limit > 0 && $attempts >= $limit) {
            return response()->error(lang('auth.resend_verification_limit_error'));
        }

        if ($limit > 0) {
            cache($attemptsKey, $attempts + 1, 3600);
        }

        $user = $this->findPendingUser($validated['email']);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $user->set('remote_key', $token);
            // Ключ один на подтверждение почты и на сброс пароля, поэтому чужие
            // данные по нему чистим — иначе ссылка проверялась бы на срок
            // жизни, назначенный сбросом пароля.
            $user->set('remote_data', null);
            $user->save();

            $this->sendNotificationEmail([
                'username' => $user->username,
                'email' => $validated['email'],
                'token' => $token,
            ]);

            Dispatcher::fire(Dispatcher::AFTER_RESEND_VERIFICATION, [
                'user' => $user,
                'email' => $validated['email'],
            ]);
        }

        return response()->success(lang('auth.resend_verification_success'));
    }

    /**
     * Пользователь, которому подтверждение ещё нужно. Активный аккаунт не
     * возвращаем: у него подтверждать нечего, а новый remote_key сломал бы уже
     * запрошенный сброс пароля.
     */
    protected function findPendingUser(string $email)
    {
        $profile = $this->modx->getObject($this->profileClassKey, ['email' => $email]);
        if (!$profile) {
            return null;
        }

        $user = $profile->getOne('User');
        if (!$user || $user->get('active')) {
            return null;
        }

        return $user;
    }
}
