<?php

namespace Boshnik\PbAuth\Http\Controllers\Auth;

use Boshnik\PbAuth\Events\Dispatcher;
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

        return view(Config::get("views.$view"), $data);
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
     * Логинит пользователя в текущем контексте и во всех дополнительных.
     */
    protected function authenticate($user): void
    {
        $this->modx->user = $user;
        $this->modx->getUser();

        $defaultContext = $this->modx->context->key ?? 'web';
        $_SESSION['modx.user.contextTokens'][$defaultContext] = $user->id;

        foreach ($this->getContexts() as $context) {
            $_SESSION['modx.user.contextTokens'][$context] = $user->id;
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
