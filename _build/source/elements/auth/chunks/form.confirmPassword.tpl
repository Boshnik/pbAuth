<form class="border rounded-4 p-5" action="{route 'confirmPassword'}" method="post" data-pbform>
    <input type="hidden" name="_token" value="{csrf_token}">
    <input type="hidden" name="honeypot" value="">

    <h3 class="text-center">{lang 'auth.form_confirm_password_title'}</h3>
    <p class="text-center px-5">{lang 'auth.form_confirm_password_subtitle'}</p>

    {if $success_message}
        <p class="form-message text-center text-success" data-pbform-message>{$success_message}</p>
    {elseif $error_message}
        <p class="form-message text-center text-error text-danger" data-pbform-message>{$error_message}</p>
    {else}
        <p class="form-message text-center d-none" data-pbform-message></p>
    {/if}

    <div class="form-group mb-3">
        <label class="mb-2" for="password">{lang 'auth.field_password'}</label>
        <input type="password" name="password" id="password" class="form-control{if $errors.password} is-invalid{/if}" required>
        <span class="invalid-feedback" data-error="password">{$errors.password}</span>
    </div>

    <button type="submit" class="btn btn-dark w-100">
        <span class="spinner spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
        <span role="status">{lang 'auth.form_confirm_password_submit'}</span>
    </button>
</form>