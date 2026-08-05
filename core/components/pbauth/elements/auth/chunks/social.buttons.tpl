{* Кнопки входа через соцсети. Показываются только настроенные провайдеры.
   Вставляется в формы входа и регистрации:
   {include 'file:auth/chunks/social.buttons.tpl'} *}
{if $social_providers}
    <div class="social-auth mt-4">
        <p class="text-center small text-muted mb-2">{lang 'auth.social_or'}</p>
        <div class="d-flex flex-wrap gap-2 justify-content-center align-items-center">
            {foreach $social_providers as $provider}
                {if $provider.redirect}
                    <a class="btn btn-outline-dark btn-sm" href="{$provider.url}">{$provider.title}</a>
                {elseif $provider.key == 'telegram'}
                    {* Кнопку рисует сам Telegram, своей вёрстки он не принимает. *}
                    <script async src="https://telegram.org/js/telegram-widget.js?22"
                            data-telegram-login="{$provider.bot_name}"
                            data-size="medium"
                            data-auth-url="{$provider.callback}"
                            data-request-access="write"></script>
                {/if}
            {/foreach}
        </div>
    </div>
{/if}
