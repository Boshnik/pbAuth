<?php

namespace PageBlocks\App\Http\Controllers\Auth;

use PageBlocks\App\Http\Controllers\Controller;
use Boshnik\PageBlocks\Facades\Request;
use Boshnik\PageBlocks\Support\Lang;

class AuthController extends Controller
{
    public $userClassKey = \modUser::class;
    public $profileClassKey = \modUserProfile::class;

    public function __construct()
    {
        parent::__construct();

        $this->modx->lexicon->load('core:default');
        $this->modx->lexicon->load('core:user');

        Lang::setLocale('en');
    }

    public function getProccesorPath(string $name): string
    {
        $map = [
            'login' => ['security/login', 'Security/Login'],
            'logout' => ['security/logout', 'Security/Logout'],
        ];

        return $map[$name][$this->modxVersion === 3 ? 1 : 0] ?? '';
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

        $this->modx->user = $user;
        $this->modx->getUser();

        $defaultContext = $this->modx->context->key ?? 'web';
        $_SESSION['modx.user.contextTokens'][$defaultContext] = $user->id;

        $contexts = $this->getContexts();
        foreach ($contexts as $context) {
            $_SESSION['modx.user.contextTokens'][$context] = $user->id;
        }

        return redirect('/');
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