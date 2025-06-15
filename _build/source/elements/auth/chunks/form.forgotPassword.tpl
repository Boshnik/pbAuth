<form class="border rounded-4 p-5" action="{route 'forgotPassword'}" method="post" data-pbform>
    <input type="hidden" name="_token" value="{csrf_token}">
    <input type="hidden" name="honeypot" value="">

    <h3 class="text-center">{lang 'auth.form_forgot_password_title'}</h3>
    <p class="text-center">{lang 'auth.form_forgot_password_subtitle'}</p>

    {if $success_message}
        <p class="form-message text-center text-success" data-pbform-message>{$success_message}</p>
    {elseif $error_message}
        <p class="form-message text-center text-error text-danger" data-pbform-message>{$error_message}</p>
    {else}
        <p class="form-message text-center d-none" data-pbform-message></p>
    {/if}

    <div class="form-group mb-3">
        <label class="mb-2" for="email">{lang 'auth.field_email'}</label>
        <input type="email" name="email" id="email" class="form-control{if $errors.email} is-invalid{/if}" value="{$old_input.email}" placeholder="email@example.com" required>
        <span class="invalid-feedback" data-error="email">{$errors.email}</span>
    </div>

    <button type="submit" class="btn btn-dark w-100">
        <span class="spinner spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
        <span role="status">{lang 'auth.form_forgot_password_submit'}</span>
    </button>

    <p class="text-center mt-3 mb-0">{lang 'auth.return_ro'} <a class="text-dark" href="{route 'pageLogin'}">{lang 'auth.log_in'}</a></p>
</form>