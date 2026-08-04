<form class="border rounded-4 p-5" action="{route 'twoFactorChallenge'}" method="post" pb-form>
    <input type="hidden" name="_token" value="{csrf_token}">

    <h3 class="text-center">{lang 'auth.form_two_factor_title'}</h3>
    <p class="text-center">{lang 'auth.form_two_factor_subtitle'}</p>

    {if $success_message}
        <p class="form-message text-center text-success" pb-message>{$success_message}</p>
    {elseif $error_message}
        <p class="form-message text-center text-error text-danger" pb-message>{$error_message}</p>
    {else}
        <p class="form-message text-center d-none" pb-message></p>
    {/if}

    <div class="form-group mb-3">
        <label class="mb-2" for="code">{lang 'auth.field_code'}</label>
        <input type="text" name="code" id="code" class="form-control text-center{if $errors.code} is-invalid{/if}"
               inputmode="numeric" autocomplete="one-time-code" autofocus
               placeholder="000000" maxlength="12" required>
        <span class="invalid-feedback" data-error="code">{$errors.code}</span>
    </div>

    <button type="submit" class="btn btn-dark w-100">
        <span class="spinner spinner-border spinner-border-sm" pb-spinner style="display:none" aria-hidden="true"></span>
        <span role="status">{lang 'auth.form_two_factor_submit'}</span>
    </button>

    <p class="text-center mt-3 mb-0 small">{lang 'auth.two_factor_backup_hint'}</p>
    <p class="text-center mt-2 mb-0"><a class="text-dark" href="{route 'pageLogin'}">{lang 'auth.return_ro'} {lang 'auth.log_in'}</a></p>
</form>
