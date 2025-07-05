<form class="border rounded-4 p-5" action="{route 'login'}" method="post" pb-form>
    <input type="hidden" name="_token" value="{csrf_token}">
    <input type="hidden" name="honeypot" value="">

    <h3 class="text-center">{lang 'auth.form_login_title'}</h3>
    <p class="text-center px-5">{lang 'auth.form_login_subtitle'}</p>

    {if $success_message}
        <p class="form-message text-center text-success" pb-message>{$success_message}</p>
    {elseif $error_message}
        <p class="form-message text-center text-error text-danger" pb-message>{$error_message}</p>
    {else}
        <p class="form-message text-center d-none" pb-message></p>
    {/if}

    <div class="form-group mb-3">
        <label class="mb-2" for="username">{lang 'auth.field_login'}</label>
        <input type="text" name="username" id="username" class="form-control{if $errors.username} is-invalid{/if}" value="{$old_input.username}" required>
        <span class="invalid-feedback" data-error="username">{$errors.username}</span>
    </div>

    <div class="form-group mb-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <label for="password">{lang 'auth.field_password'}</label>
            <button type="button" class="text-dark btn btn-link p-0" data-bs-target="#authForgotPassword" data-bs-toggle="modal">{lang 'auth.forgot_password'}</button>
        </div>
        <input type="password" name="password" id="password" class="form-control{if $errors.password} is-invalid{/if}" required>
        <span class="invalid-feedback" data-error="password">{$errors.password}</span>
    </div>

    <div class="form-group mb-3 form-check">
        <input type="checkbox" name="remember" id="remember" class="form-check-input">
        <label for="remember" class="form-check-label">{lang 'auth.remember'}</label>
    </div>

    <button type="submit" class="btn btn-dark w-100">
        <span class="spinner spinner-border spinner-border-sm" pb-spinner style="display:none" aria-hidden="true"></span>
        <span role="status">{lang 'auth.form_login_submit'}</span>
    </button>

    <p class="text-center mt-3 mb-0 d-flex align-items-center justify-content-center gap-2">
        <span>{lang 'auth.signup_prompt'}</span>
        <button type="button" class="text-dark btn btn-link p-0" data-bs-target="#authRegister" data-bs-toggle="modal">{lang 'auth.sign_up'}</button>
    </p>
</form>