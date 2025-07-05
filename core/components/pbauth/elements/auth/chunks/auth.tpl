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
            <a class="btn btn-sm" href="{route 'pageLogin'}">{lang 'auth.login_title'}</a>
        </li>
        <li class="nav-item">
            <a class="btn btn-light btn-sm" href="{route 'pageRegister'}">{lang 'auth.register_title'}</a>
        </li>
    </ul>
{/guest}