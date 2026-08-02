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


### Files and updates

Routes ship with the component (`core/components/pbauth/routes/`) and are registered
from `bootstrap.php` via `Route::addRoutesPath()`, so a fix in them reaches every site
on upgrade. If `core/App/routes/auth.php` exists, the site's copy wins and the
component keeps its own routes out of the table — delete that file to switch over. An
upgrade removes it for you when it is still byte-identical to the shipped one.

Controllers, templates and language files are copied into the site-owned `core/App/`.
Neither install nor upgrade ever overwrites an existing file there: once it is in
`App/`, it belongs to the site. What the installer actually placed is recorded in
`core/App/.pbauth-installed.json` with hashes, and uninstall removes only the entries
that still match — anything you edited stays.

### TODO
 - Two-factor authentication (2FA)
 - Authentication and registration via social networks
 - Resending confirmation link if it was lost or not delivered