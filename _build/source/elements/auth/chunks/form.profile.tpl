<form class="border rounded-4 p-5" action="{route 'updateProfile'}" method="post" data-pbform data-noclear>
    <input type="hidden" name="_token" value="{csrf_token}">
    <input type="hidden" name="honeypot" value="">

    <h3 class="text-center">{lang 'auth.form_profile_title'}</h3>

    {if $success_message}
        <p class="form-message text-center text-success" data-pbform-message>{$success_message}</p>
    {elseif $error_message}
        <p class="form-message text-center text-error text-danger" data-pbform-message>{$error_message}</p>
    {else}
        <p class="form-message text-center d-none" data-pbform-message></p>
    {/if}

    <div class="form-group mb-3">
        <label class="mb-2" for="username">{lang 'auth.field_username'}</label>
        <input type="text" name="username" id="username" class="form-control{if $errors.username} is-invalid{/if}" value="{$old_input.username ?: $modx->user->username}" required>
        <span class="invalid-feedback" data-error="username">{$errors.username}</span>
    </div>

    <div class="form-group mb-3">
        <label class="mb-2" for="fullname">{lang 'auth.field_fullname'}</label>
        <input type="text" name="fullname" id="fullname" class="form-control{if $errors.fullname} is-invalid{/if}" value="{$old_input.fullname ?: $modx->user->fullname}">
        <span class="invalid-feedback" data-error="fullname">{$errors.fullname}</span>
    </div>

    <div class="form-group mb-3">
        <label class="mb-2" for="email">{lang 'auth.field_email'}</label>
        <input type="email" name="email" id="email" class="form-control{if $errors.email} is-invalid{/if}" value="{$old_input.email ?: $modx->user->email}" required>
        <span class="invalid-feedback" data-error="email">{$errors.email}</span>
    </div>

    <div class="form-group mb-3">
        <label class="mb-2" for="phone">{lang 'auth.field_phone'}</label>
        <input type="text" name="phone" id="phone" class="form-control{if $errors.phone} is-invalid{/if}" value="{$modx->user->phone}">
        <span class="invalid-feedback" data-error="phone">{$errors.phone}</span>
    </div>

    <button type="submit" class="btn btn-dark w-100">
        <span class="spinner spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
        <span role="status">{lang 'auth.form_profile_submit'}</span>
    </button>
</form>