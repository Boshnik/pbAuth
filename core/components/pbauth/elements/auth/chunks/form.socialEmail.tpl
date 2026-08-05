<form class="border rounded-4 p-5" action="{route 'socialEmail'}" method="post" pb-form>
    <input type="hidden" name="_token" value="{csrf_token}">

    <h3 class="text-center">{lang 'auth.form_social_email_title'}</h3>
    <p class="text-center">{lang 'auth.form_social_email_subtitle'}</p>

    {if $success_message}
        <p class="form-message text-center text-success" pb-message>{$success_message}</p>
    {elseif $error_message}
        <p class="form-message text-center text-error text-danger" pb-message>{$error_message}</p>
    {else}
        <p class="form-message text-center d-none" pb-message></p>
    {/if}

    <div class="form-group mb-3">
        <label class="mb-2" for="email">{lang 'auth.field_email'}</label>
        <input type="email" name="email" id="email" class="form-control{if $errors.email} is-invalid{/if}"
               value="{$old_input.email}" placeholder="email@example.com" autofocus required>
        <span class="invalid-feedback" data-error="email">{$errors.email}</span>
    </div>

    <button type="submit" class="btn btn-dark w-100">
        <span class="spinner spinner-border spinner-border-sm" pb-spinner style="display:none" aria-hidden="true"></span>
        <span role="status">{lang 'auth.form_social_email_submit'}</span>
    </button>
</form>
