<form class="border rounded-4 p-5" action="{route 'changePassword'}" method="post" pb-form>
    <input type="hidden" name="_token" value="{csrf_token}">

    <h3 class="text-center">{lang 'auth.form_change_password_title'}</h3>
    <p class="text-center px-5">{lang 'auth.form_change_password_subtitle'}</p>

    {if $success_message}
        <p class="form-message text-center text-success" pb-message>{$success_message}</p>
    {elseif $error_message}
        <p class="form-message text-center text-error text-danger" pb-message>{$error_message}</p>
    {else}
        <p class="form-message text-center d-none" pb-message></p>
    {/if}

    <div class="form-group mb-3">
        <label class="mb-2" for="old_password">{lang 'auth.field_current_password'}</label>
        <input type="old_password" name="old_password" id="old_password" class="form-control{if $errors.old_password} is-invalid{/if}" required>
        <span class="invalid-feedback" data-error="old_password">{$errors.old_password}</span>
    </div>

    <div class="form-group mb-3">
        <label class="mb-2" for="password">{lang 'auth.field_new_password'}</label>
        <input type="password" name="password" id="password" class="form-control{if $errors.password} is-invalid{/if}" required>
        <span class="invalid-feedback" data-error="password">{$errors.password}</span>
    </div>

    <div class="form-group mb-3">
        <label class="mb-2" for="password_confirmation">{lang 'auth.field_confirm_password'}</label>
        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
        <span class="invalid-feedback" data-error="password_confirmation"></span>
    </div>

    <button type="submit" class="btn btn-dark w-100">
        <span class="spinner spinner-border spinner-border-sm" pb-spinner style="display: none" aria-hidden="true"></span>
        <span role="status">{lang 'auth.form_change_password_submit'}</span>
    </button>
</form>