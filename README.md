# pbAuth

Login, registration, password recovery and user profile for sites built on
[PageBlocks](https://pageblocks.pro) 3.

**📖 Documentation — [pbauth.pageblocks.pro](https://pbauth.pageblocks.pro)**
([English](https://pbauth.pageblocks.pro/docs/) ·
[Русский](https://pbauth.pageblocks.pro/ru/docs/))

[DEMO](https://pbauth.boshnik.com/)

### Features

 - `/login`, `/register`, `/profile` answer right after install — no resources to create
 - password reset, password change, password confirmation before a risky action
 - sign in by username, e-mail or phone number
 - registration with e-mail confirmation, and resending the link when it was lost
 - user profile with editable fields and avatar upload
 - adding users to groups
 - validation and error display through Fenom, CSRF protection, flash messages
 - registration form protection with Google reCAPTCHA v3 and a honeypot field
 - two-factor confirmation at login (TOTP), with backup codes
 - optional one-session-per-account: a new sign-in closes the previous one
 - sign in through Google, Yandex, Mail.ru, GitHub, Facebook and Telegram
 - "Log in as user" and "View on the site" buttons on users in the manager
 - fourteen events, replaceable controllers, everything configured from one file

### Quick start

1. Install **PageBlocks 3**, then pbAuth.
2. **System → System Settings**, filter `pageblocks`: set `pageblocks_routing`
   to **Route Only** or **Full API** — otherwise `/login` and `/register` answer
   "page not found".
3. Enable `pageblocks_load_scripts` so the forms submit without a page reload
   and errors highlight in place.
4. Clear the site cache and open `/login`.

Then add one of the shipped chunks to your site header —
`{include 'file:auth/chunks/auth.tpl'}` for links to separate pages, or
`{include 'file:auth/chunks/auth_modal.tpl'}` for modal windows. Both know
whether the visitor is signed in.

The social sign-in migration (`pba_social_accounts`) runs at install. Where
`exec()` is disabled on the host, the resolver says so in the log and the
migration has to be applied by hand.

### Configuration

Create nothing — the installer puts a stub at `core/App/config/pbauth.php`, with
every setting listed and commented out. The file belongs to the site; pbAuth only
reads it, so an upgrade cannot overwrite it. Write **only what differs** from
`core/components/pbauth/config/defaults.php`.

```php
return [
    'views'   => ['profile' => 'file:templates/profile'],
    'rules'   => ['profile' => ['phone' => 'required|string', 'fullname' => null]],
    'listeners' => [
        \Boshnik\PbAuth\Events\Dispatcher::USER_SAVING => [MyExtendedFields::class],
    ],
];
```

Dictionaries merge by key, lists are replaced whole. `rules` merges field by
field: an unknown field is added, a known one replaced, `null` drops a shipped
one.

When the change is behaviour rather than data, hook an event —
`pbAuthUserSaving`, `pbAuthAfterRegister`, `pbAuthAfterLogin`,
`pbAuthAfterLogout`, `pbAuthAfterProfileUpdate`, `pbAuthAfterVerifyEmail`,
`pbAuthAfterResetPassword`, `pbAuthAfterChangePassword` and six more. A listener
is a class with `handle(array $params, string $event)` or any callable; an
exception in one is logged and does not abort the action. The matching MODX
system event fires as well, so plugins can listen too — but listeners in the
config need no re-attaching after a deploy.

When even that is not enough, subclass a shipped controller and name your class
in `controllers` — the component's routes start pointing at it.

Routes and controllers stay in the component, so a fix in them reaches every
site on upgrade. What a site used to change by editing copied controllers is
configuration now.

### Files and updates

Templates and language files are copied into the site-owned `core/App/`: every
site draws them differently and no amount of polymorphism helps there. Neither
install nor upgrade ever overwrites an existing file — once it is in `App/`, it
belongs to the site. What the installer actually placed is recorded in
`core/App/.pbauth-installed.json` with hashes, and uninstall removes only the
entries that still match, so anything you edited stays.

If `core/App/routes/auth.php` exists, the site is left on the old layout
entirely: its routes and its controllers in `App/Http/Controllers/Auth/` keep
working and the component registers nothing, so no URI is served twice. An
upgrade removes that file when it is still byte-identical to the shipped one;
otherwise delete it yourself once your changes have moved into
`App/config/pbauth.php`.

### TODO

 - Sign in with Apple, VK ID
 - extract the sign-in and registration logic into a service with a public API,
   and add snippets over it — today the logic lives in the controllers, so there
   is no one-line call for a templater and nothing to unit-test without MODX
