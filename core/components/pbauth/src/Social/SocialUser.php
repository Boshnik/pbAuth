<?php

namespace Boshnik\PbAuth\Social;

/**
 * То, что удалось узнать о человеке у провайдера.
 *
 * `emailVerified` тут — не украшение, а основа решения о связывании аккаунтов:
 * входить в существующий профиль по совпадению почты можно только тогда, когда
 * провайдер эту почту проверял. Иначе достаточно завести у него аккаунт с чужим
 * адресом, чтобы забрать чужой профиль на сайте.
 */
class SocialUser
{
    public function __construct(
        public string $id,
        public string $email = '',
        public bool $emailVerified = false,
        public string $nickname = '',
        public string $avatar = '',
        public array $raw = []
    ) {
    }

    public function hasEmail(): bool
    {
        return $this->email !== '' && filter_var($this->email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
