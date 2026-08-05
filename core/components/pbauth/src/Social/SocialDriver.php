<?php

namespace Boshnik\PbAuth\Social;

/**
 * Драйвер входа через стороннюю службу.
 *
 * Не все они устроены как OAuth2: Telegram, например, не уводит никуда, а
 * отдаёт подписанные данные прямо на странице. Поэтому интерфейс описывает не
 * «шаги OAuth», а две вещи, которые нужны компоненту: куда отправить человека и
 * кем он вернулся.
 */
interface SocialDriver
{
    /** Ключ провайдера: `google`, `telegram` и так далее. */
    public static function key(): string;

    /** Заданы ли доступы. Ненастроенный провайдер просто не показывается. */
    public function isConfigured(): bool;

    /**
     * Уводит ли провайдер на свою страницу. false — работает виджетом прямо у
     * нас (Telegram), и тогда одноразовой метки в обмене нет.
     */
    public function isRedirectBased(): bool;

    /**
     * Адрес, куда уводим пользователя. Пустая строка — провайдер никуда не
     * уводит и работает прямо на странице (виджет).
     *
     * @param string $state одноразовая метка против подделки запроса
     */
    public function redirectUrl(string $redirectUri, string $state): string;

    /**
     * Кто вернулся. null — не удалось подтвердить личность.
     *
     * @param array $request данные, с которыми провайдер вернул пользователя
     */
    public function user(array $request, string $redirectUri): ?SocialUser;
}
