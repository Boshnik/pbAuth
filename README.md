# pbAuth

[DEMO](https://pbauth.boshnik.com/)

pbAuth – a powerful authentication, registration, and user profile management system for PageBlocks

### Features
 - Authentication and registration via POST requests
 - Password reset and change
 - User profile with editable data
 - Avatar upload
 - Adding users to groups
 - Validation and error display using Fenom
 - CSRF protection and flash messages support
 - Extendable controllers and templates
 - Registration form protection with Google reCAPTCHA v3

### Quick Start
**1. Enabling Routing**

For pbAuth to work, you need to enable routing in PageBlocks. Make sure the system setting **pageblocks_routing** is set to either **Route Only** or **Full API**.

**2. Connecting JavaScript for Forms**

If you want authorization and registration forms to work without page reload, enable the **pageblocks_load_scripts** setting. This will automatically connect the necessary scripts that handle form submission via AJAX, as well as error and message display.

**3. Adding the Authentication Chunk**

It's recommended to add one of the ready-made file chunks to your site header:
 - **auth** - classic panel with login/registration buttons and profile display
 - **auth_modal** - modal window (if you want to embed forms without separate pages)


### Extending

Подробное руководство по-русски — [docs/guide.ru.md](core/components/pbauth/docs/guide.ru.md):
что где лежит, как добавить поле, шаблон, событие или свой контроллер.

Routes and controllers stay in the component, so a fix in them reaches every site on
upgrade. What a site used to change by editing the copied controllers is configuration
now. Create `core/App/config/pbauth.php` — the file belongs to the site, pbAuth only
reads it — and override just what differs from
`core/components/pbauth/config/defaults.php`. A sample is in
`docs/pbauth.config.example.php`.

```php
return [
    'views'   => ['profile' => 'file:templates/profile'],
    'rules'   => ['profile' => ['phone' => 'required|string', 'fullname' => null]],
    'listeners' => [
        \Boshnik\PbAuth\Events\Dispatcher::USER_SAVING => [MyExtendedFields::class],
    ],
];
```

`rules` is merged field by field: an unknown field is added, a known one replaced,
`null` drops a shipped one. When the change is behaviour rather than data, hook an
event — `pbAuthUserSaving`, `pbAuthAfterRegister`, `pbAuthAfterLogin`,
`pbAuthAfterLogout`, `pbAuthAfterProfileUpdate`, `pbAuthAfterVerifyEmail`,
`pbAuthAfterResetPassword`, `pbAuthAfterChangePassword`. A listener is a class with
`handle(array $params, string $event)` or any callable; an exception in one is logged
and does not abort the action. The matching MODX system event is invoked as well, so
plugins can listen too — but listeners in the config need no re-attaching after a
deploy. When even that is not enough, subclass the shipped controller and name your
class in `controllers` — the component's routes start pointing at it.

### Files and updates

Templates and language files are copied into the site-owned `core/App/`: every site
draws them differently and no amount of polymorphism helps there. Neither install nor
upgrade ever overwrites an existing file — once it is in `App/`, it belongs to the
site. What the installer actually placed is recorded in
`core/App/.pbauth-installed.json` with hashes, and uninstall removes only the entries
that still match, so anything you edited stays.

If `core/App/routes/auth.php` exists, the site is left on the old layout entirely:
its routes and its controllers in `App/Http/Controllers/Auth/` keep working and the
component registers nothing, so no URI is served twice. An upgrade removes that file
when it is still byte-identical to the shipped one; otherwise delete it yourself once
your changes have moved into `App/config/pbauth.php`.

### TODO
 - Two-factor authentication (2FA)
 - Authentication and registration via social networks
 - Resending confirmation link if it was lost or not delivered