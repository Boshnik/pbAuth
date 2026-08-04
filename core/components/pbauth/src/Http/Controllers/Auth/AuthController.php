<?php

namespace Boshnik\PbAuth\Http\Controllers\Auth;

use Boshnik\PbAuth\Events\Dispatcher;
use Boshnik\PageBlocks\Support\Mail;
use Boshnik\PbAuth\Support\Config;
use PageBlocks\App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public $userClassKey = \modUser::class;
    public $profileClassKey = \modUserProfile::class;

    protected int $modxVersion = 3;

    public function __construct(\modX $modx)
    {
        parent::__construct($modx);

        $this->modxVersion = (int)($this->modx->getVersionData()['version'] ?? 3);

        $this->modx->lexicon->load('core:default');
        $this->modx->lexicon->load('core:user');
    }

    public function getProccesorPath(string $name): string
    {
        $map = [
            'login' => ['security/login', 'Security/Login'],
            'logout' => ['security/logout', 'Security/Logout'],
        ];

        return $map[$name][$this->modxVersion === 3 ? 1 : 0] ?? '';
    }

    /**
     * Страница компонента: шаблон-обёртка и чанк формы берутся из конфига,
     * чтобы сайт менял вёрстку, не переписывая контроллер.
     */
    protected function page(string $view, string $action, array $data = [])
    {
        $form = Config::get("forms.$action");
        if ($form !== null && !array_key_exists('form', $data)) {
            $data['form'] = $form;
        }

        // Меню профиля должно знать, показывать ли раздел второго фактора, а
        // рисуется оно в шаблоне-обёртке любой страницы профиля.
        $data['two_factor_available'] = (bool)Config::get('two_factor_enabled', true);

        // Обычно хватает двух обёрток на все страницы, но отдельной странице
        // можно назначить свою: `views.two_factor` перебивает `views.profile`.
        $template = Config::get("views.$action") ?: Config::get("views.$view");

        return view($template, $data);
    }

    protected function redirectTo(string $action, string $default = '/'): string
    {
        return Config::get("redirects.$action", $default);
    }

    public function getContexts(): array
    {
        $addContexts = [];
        if (!$this->modx->getOption('pageblocks_context_aware', null, false)) {
            return $addContexts;
        }

        $contexts = $this->modx->getOption('pageblocks_contexts', null, '');
        if (!empty($contexts)) {
            $contexts = json_decode($contexts, true);
            $addContexts = array_column($contexts, 'key');
        }

        $defaultContext = $this->modx->context->key ?? 'web';
        $addContexts = array_filter($addContexts, fn($context) => $context !== $defaultContext);

        return array_values($addContexts);
    }

    /**
     * Открывает пользователю сессию в текущем контексте и во всех дополнительных.
     *
     * Через addSessionContext(), а не правкой $_SESSION напрямую: MODX при этом
     * ещё и обновляет отметку последнего входа и внутреннее состояние
     * пользователя. Процессор входа тут не подходит — им пользуются те места,
     * где пароля на руках уже нет: подтверждение почты, второй фактор, вход
     * менеджера под чужой учётной записью.
     */
    protected function authenticate($user): void
    {
        $defaultContext = $this->modx->context->key ?? 'web';

        $user->addSessionContext($defaultContext);

        foreach ($this->getContexts() as $context) {
            $user->addSessionContext($context);
        }
    }

    public function verifyEmail(string $token)
    {
        if (!$user = $this->modx->getObject($this->userClassKey, ['remote_key' => $token])) {
            return redirect(route('login'));
        }

        if ($user->active) {
            return redirect(route('login'));
        }

        $user->set('active', true);
        $user->set('remote_key', null);
        $user->set('remote_data', null);
        $user->save();

        $this->authenticate($user);

        Dispatcher::fire(Dispatcher::AFTER_VERIFY_EMAIL, ['user' => $user]);

        return redirect($this->redirectTo('verify_email'));
    }

    /**
     * Письмо со ссылкой подтверждения. Живёт здесь, а не в RegisterController,
     * потому что то же письмо шлёт и повторная отправка.
     */
    protected function sendNotificationEmail(array $data): void
    {
        $username = htmlspecialchars($data['username'] ?? '', ENT_QUOTES, 'UTF-8');
        $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        $token = preg_replace('/[^a-f0-9]/i', '', $data['token'] ?? '');

        if (!$email || !$token) {
            return;
        }

        $verifyUrl = MODX_SITE_URL . 'verify-email/' . $token;

        Mail::to($email)
            ->subject(lang('auth.register_subject'))
            ->view('file:auth/chunks/email.verifyEmail', [
                'username' => $username,
                'email' => $email,
                'verifyUrl' => $verifyUrl,
            ])
            ->send();
    }

    public function getProcessorError($response)
    {
        $errors = [];
        $message = $response->getMessage();
        foreach ($response->getAllErrors() as $error) {
            if (strpos($error, ':') !== false) {
                [$key, $value] = explode(': ', $error, 2);
                $errors[$key] = trim($value);
                if (empty($message)) {
                    $message = trim($value);
                }
            }
        }

        return response()->error($message, $errors);
    }
}
