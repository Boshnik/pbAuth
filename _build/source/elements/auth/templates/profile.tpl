{extends 'file:auth/templates/auth.tpl'}

{block 'content'}
    <div class="row mx-auto my-5">
        <div class="col-12 col-md-3">
            <ul class="list-group list-group-flush">
                <li class="list-group-item{if $form == 'form.profile'} fw-bold{/if}">
                    <a href="{route 'pageProfile'}" class="nav-link text-dark">{lang 'auth.profile_title'}</a>
                </li>
                <li class="list-group-item{if $form == 'form.changePassword'} fw-bold{/if}">
                    <a href="{route 'pageChangePassword'}" class="nav-link text-dark">{lang 'auth.change_password_title'}</a>
                </li>
                <li class="list-group-item">
                    <a href="{route 'logout'}" class="nav-link text-danger">Logout</a>
                </li>
            </ul>
        </div>
        <div class="col-12 col-md-5 mx-5">
            {set $chunkPath = 'file:auth/chunks/' ~ $form}
            {include $chunkPath}
        </div>
    </div>
{/block}