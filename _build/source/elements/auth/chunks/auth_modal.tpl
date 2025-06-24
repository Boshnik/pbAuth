{auth}
    <a href="{route 'pageProfile'}" class="d-flex align-items-center gap-2 text-dark text-decoration-none">
        <div class="avatar d-flex align-items-center justify-content-center rounded-5 bg-secondary-subtle overflow-hidden" style="width:48px;height:48px">
            <img src="{$modx->user->photo ?: $modx->user->getGravatar()}" width="48" height="48" alt="{$modx->user->username}">
        </div>
        <div class="d-flex flex-column">
            <strong>{$modx->user->username}</strong>
            <span>{$modx->user->email}</span>
        </div>
    </a>
{/auth}

{guest}
    <ul class="navbar-nav d-flex align-items-center list-unstyled mb-0">
        <li class="nav-item">
            <button class="btn btn-sm" data-bs-target="#authLogin" data-bs-toggle="modal">{lang 'auth.login_title'}</button>
        </li>
        <li class="nav-item">
            <button class="btn btn-light btn-sm" data-bs-target="#authRegister" data-bs-toggle="modal">{lang 'auth.register_title'}</button>
        </li>
    </ul>

    <div class="modal fade" id="authLogin" aria-hidden="true" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                {insert 'file:auth/chunks/modals/form.login.tpl'}
            </div>
        </div>
    </div>
    <div class="modal fade" id="authRegister" aria-hidden="true" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                {insert 'file:auth/chunks/modals/form.register.tpl'}
            </div>
        </div>
    </div>
    <div class="modal fade" id="authForgotPassword" aria-hidden="true" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                {insert 'file:auth/chunks/modals/form.forgotPassword.tpl'}
            </div>
        </div>
    </div>
{/guest}