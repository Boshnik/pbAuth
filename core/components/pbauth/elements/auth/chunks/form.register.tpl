<form class="border rounded-4 p-5" action="{route 'register'}" method="post" pb-form>
    <input type="hidden" name="_token" value="{csrf_token}">
    <input type="hidden" name="honeypot" value="">

    <h3 class="text-center">{lang 'auth.form_register_title'}</h3>
    <p class="text-center px-5">{lang 'auth.form_register_subtitle'}</p>

    {if $success_message}
        <p class="form-message text-center text-success" pb-message>{$success_message}</p>
    {elseif $error_message}
        <p class="form-message text-center text-error text-danger" pb-message>{$error_message}</p>
    {else}
        <p class="form-message text-center d-none" pb-message></p>
    {/if}

    <div class="form-group mb-3">
        <label class="mb-2" for="username">{lang 'auth.field_username'}</label>
        <input type="text" name="username" id="username" class="form-control{if $errors.username} is-invalid{/if}" value="{$old_input.username}" required>
        <span class="invalid-feedback" data-error="username">{$errors.username}</span>
    </div>

    <div class="form-group mb-3">
        <label class="mb-2" for="email">{lang 'auth.field_email'}</label>
        <input type="text" name="email" id="email" class="form-control{if $errors.email} is-invalid{/if}" value="{$old_input.email}" required>
        <span class="invalid-feedback" data-error="email">{$errors.email}</span>
    </div>

    <div class="form-group mb-3">
        <label class="mb-2" for="password">{lang 'auth.field_password'}</label>
        <input type="password" name="password" id="password" class="form-control{if $errors.password} is-invalid{/if}" required>
        <span class="invalid-feedback" data-error="password">{$errors.password}</span>
    </div>

    <div class="form-group mb-3">
        <label class="mb-2" for="password_confirmation">{lang 'auth.field_confirm_password'}</label>
        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
        <span class="invalid-feedback" data-error="password_confirmation"></span>
    </div>

    <button type="submit" class="btn btn-dark w-100">
        <span class="spinner spinner-border spinner-border-sm" pb-spinner style="display:none" aria-hidden="true"></span>
        <span role="status">{lang 'auth.form_register_submit'}</span>
    </button>

    <p class="text-center mt-3 mb-0">{lang 'auth.login_prompt'} <a class="text-dark" href="{route 'pageLogin'}">{lang 'auth.log_in'}</a></p>
</form>