{* Привязанные сети в профиле. Данные приносит ProfileController. *}
{if $social_providers}
    <div class="social-accounts border rounded-4 p-4 mt-4">
        <h5>{lang 'auth.social_accounts_title'}</h5>
        <ul class="list-group list-group-flush">
            {foreach $social_providers as $provider}
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span>{$provider.title}</span>
                    {if $social_linked[$provider.key]}
                        <form action="{route 'socialUnlink', ['provider' => $provider.key]}" method="post" pb-form class="m-0">
                            <input type="hidden" name="_token" value="{csrf_token}">
                            <button type="submit" class="btn btn-sm btn-outline-danger">{lang 'auth.social_unlink'}</button>
                        </form>
                    {elseif $provider.redirect}
                        <a class="btn btn-sm btn-outline-dark" href="{$provider.url}">{lang 'auth.social_link'}</a>
                    {/if}
                </li>
            {/foreach}
        </ul>
    </div>
{/if}
