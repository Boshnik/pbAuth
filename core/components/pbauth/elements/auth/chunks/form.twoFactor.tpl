{if $success_message}
    <p class="form-message text-success" pb-message>{$success_message}</p>
{elseif $error_message}
    <p class="form-message text-error text-danger" pb-message>{$error_message}</p>
{/if}

{if $backup_codes}
    {* Показываются ровно один раз - после включения или перевыпуска. *}
    <div class="border border-warning rounded-4 p-4 mb-4">
        <h5>{lang 'auth.two_factor_codes_title'}</h5>
        <p class="mb-3 small">{lang 'auth.two_factor_codes_warning'}</p>
        <ul class="list-unstyled font-monospace mb-0">
            {foreach $backup_codes as $code}
                <li>{$code}</li>
            {/foreach}
        </ul>
    </div>
{/if}

{if $two_factor_on}

    <div class="border rounded-4 p-4 mb-4">
        <h4>{lang 'auth.two_factor_settings_title'}</h4>
        <p class="text-success mb-2">{lang 'auth.two_factor_is_on'}</p>
        <p class="small mb-0">{lang 'auth.two_factor_codes_left'}: {$backup_codes_left}</p>
    </div>

    <form class="border rounded-4 p-4 mb-4" action="{route 'twoFactorBackupCodes'}" method="post" pb-form>
        <input type="hidden" name="_token" value="{csrf_token}">
        <h5>{lang 'auth.two_factor_regenerate_title'}</h5>
        <p class="small">{lang 'auth.two_factor_regenerate_hint'}</p>
        <div class="form-group mb-3">
            <label class="mb-2" for="regen_password">{lang 'auth.field_current_password'}</label>
            <input type="password" name="password" id="regen_password" class="form-control{if $errors.password} is-invalid{/if}" required>
            <span class="invalid-feedback" data-error="password">{$errors.password}</span>
        </div>
        <button type="submit" class="btn btn-outline-dark">
            <span class="spinner spinner-border spinner-border-sm" pb-spinner style="display:none" aria-hidden="true"></span>
            <span role="status">{lang 'auth.two_factor_regenerate_submit'}</span>
        </button>
    </form>

    <form class="border border-danger rounded-4 p-4" action="{route 'twoFactorDisable'}" method="post" pb-form>
        <input type="hidden" name="_token" value="{csrf_token}">
        <h5>{lang 'auth.two_factor_disable_title'}</h5>
        <p class="small">{lang 'auth.two_factor_disable_hint'}</p>
        <div class="form-group mb-3">
            <label class="mb-2" for="disable_password">{lang 'auth.field_current_password'}</label>
            <input type="password" name="password" id="disable_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-danger">
            <span class="spinner spinner-border spinner-border-sm" pb-spinner style="display:none" aria-hidden="true"></span>
            <span role="status">{lang 'auth.two_factor_disable_submit'}</span>
        </button>
    </form>

{else}

    <form class="border rounded-4 p-4" action="{route 'twoFactorEnable'}" method="post" pb-form>
        <input type="hidden" name="_token" value="{csrf_token}">

        <h4>{lang 'auth.two_factor_settings_title'}</h4>
        <p>{lang 'auth.two_factor_setup_intro'}</p>

        <ol class="mb-4">
            <li class="mb-3">
                {lang 'auth.two_factor_step_app'}
            </li>
            <li class="mb-3">
                {lang 'auth.two_factor_step_secret'}
                {* На телефоне ссылка открывает приложение сама. На компьютере
                   секрет вводят руками - он рядом, разбит по четыре знака.
                   Хотите QR - нарисуйте его любой JS-библиотекой из этого uri. *}
                <p class="mt-2 mb-1">
                    <a href="{$otpauth_uri}" data-pbauth-totp-uri="{$otpauth_uri}">{lang 'auth.two_factor_open_app'}</a>
                </p>
                <p class="font-monospace border rounded p-2 mb-0 d-inline-block">{$secret_readable}</p>
            </li>
            <li>
                {lang 'auth.two_factor_step_code'}
            </li>
        </ol>

        <div class="form-group mb-3">
            <label class="mb-2" for="code">{lang 'auth.field_code'}</label>
            <input type="text" name="code" id="code" class="form-control{if $errors.code} is-invalid{/if}"
                   inputmode="numeric" autocomplete="one-time-code" placeholder="000000" maxlength="6" required>
            <span class="invalid-feedback" data-error="code">{$errors.code}</span>
        </div>

        <button type="submit" class="btn btn-dark">
            <span class="spinner spinner-border spinner-border-sm" pb-spinner style="display:none" aria-hidden="true"></span>
            <span role="status">{lang 'auth.two_factor_enable_submit'}</span>
        </button>
    </form>

{/if}
