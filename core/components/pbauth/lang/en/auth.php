<?php

return [
    // Controllers
    'user_confirm_password_success' => 'Your password has been confirmed.',
    'forgot_password_success' => 'If the address is registered and confirmed, we have sent a link to reset the password. Check your inbox and the spam folder.',
    'update_profile_success' => 'Profile successfully updated.',
    'register_error' => 'Could not register user.',
    'register_success' => 'Check your email for the confirmation link.',
    'invalid_token' => 'Invalid Token',
    'old_password_error' => 'Old password is incorrect',
    'change_password_success' => 'Password successfully changed',
    'recaptcha_failed' => 'reCAPTCHA verification failed. Please try again.',
    'register_ip_error' => 'Too many registrations from your IP. Try again later.',

    // Title
    'reset_password_title' => 'Reset Password',
    'register_title' => 'Register',
    'profile_title' => 'Profile',
    'login_title' => 'Login',
    'forgot_password_title' => 'Forgot Password',
    'confirm_password_title' => 'Confirm Password',
    'change_password_title' => 'Change Password',

    // Emails
    'register_subject' => 'Confirm your account',
    'reset_password_subject' => 'Reset Password',

    // Form
    'forgot_password' => 'Forgot your password?',
    'remember' => 'Remember me',
    'log_in' => 'Log in',
    'sign_up' => 'Sign up',
    'signup_prompt' => 'Don\'t have an account?',
    'login_prompt' => 'Already have an account?',
    'return_ro' => 'Or, return to',
    'delete_photo' => 'Delete photo',

    'field_login' => 'Login or Email address',
    'field_username' => 'Name',
    'field_fullname' => 'Fullname',
    'field_password' => 'Password',
    'field_new_password' => 'New password',
    'field_current_password' => 'Current password',
    'field_confirm_password' => 'Confirm password',
    'field_email' => 'Email address',
    'field_phone' => 'Phone',

    // form login
    'form_login_title' => 'Log in to your account',
    'form_login_subtitle' => 'Enter your login or email, and your password below to log in',
    'form_login_submit' => 'Log in',
    // form register
    'form_register_title' => 'Create an account',
    'form_register_subtitle' => 'Enter your infomation bellow to create account',
    'form_register_submit' => 'Create Account',
    // form reset password
    'form_reset_password_title' => 'Reset password',
    'form_reset_password_subtitle' => 'Please enter your new password below',
    'form_reset_password_submit' => 'Reset password',
    // form forgot password
    'form_forgot_password_title' => 'Forgot password',
    'form_forgot_password_subtitle' => 'Enter your email to receive a password reset link',
    'form_forgot_password_submit' => 'Email password reset link',
    // form configrm password
    'form_confirm_password_title' => 'Confirm password',
    'form_confirm_password_subtitle' => 'This is a secure area of the application. Please confirm your password before continuing.',
    'form_confirm_password_submit' => 'Confirm',
    // form change password
    'form_change_password_title' => 'Update password',
    'form_change_password_subtitle' => 'Ensure your account is using a long, random password to stay secure',
    'form_change_password_submit' => 'Save',
    // form profile
    'form_profile_title' => 'Profile',
    'form_profile_submit' => 'Save',

    // impersonate
    'impersonate_no_manager' => 'Manager login required',
    'impersonate_denied' => 'Not enough permissions',
    'impersonate_not_found' => 'User not found',
    'impersonate_blocked' => 'User is blocked',

    // resend verification
    'resend_verification_title' => 'Resend confirmation link',
    'resend_verification_success' => 'If the address is registered and not confirmed yet, we have sent the link again. Check your inbox and the spam folder.',
    'resend_verification_limit_error' => 'Too many attempts. Try again in an hour.',
    'form_resend_verification_title' => 'Confirmation link',
    'form_resend_verification_subtitle' => 'Lost the letter? Enter your address and we will send the link again.',
    'form_resend_verification_submit' => 'Send the link',
    'resend_prompt' => 'Never received the confirmation letter?',
    'resend_link' => 'Send it again',
    'forgot_password_limit_error' => 'Too many attempts. Try again in an hour.',

    // two factor
    'two_factor_title' => 'Two-step confirmation',
    'two_factor_settings_title' => 'Two-step confirmation',
    'field_code' => 'Code from the app',
    'form_two_factor_title' => 'One more step',
    'form_two_factor_subtitle' => 'Open your authenticator app and enter the current code.',
    'form_two_factor_submit' => 'Confirm',
    'two_factor_backup_hint' => 'Lost the phone? Enter one of your backup codes instead.',
    'two_factor_invalid' => 'The code did not match. Check the time on your phone and try the next one.',
    'two_factor_expired' => 'The wait has expired. Please sign in again.',
    'two_factor_too_many' => 'Too many wrong codes. Please sign in again.',
    'two_factor_setup_expired' => 'Setup has expired. Reload the page and start again.',
    'two_factor_already_enabled' => 'Two-step confirmation is already on.',
    'two_factor_not_enabled' => 'Two-step confirmation is off.',
    'two_factor_is_on' => 'Two-step confirmation is on.',
    'two_factor_enabled_success' => 'Two-step confirmation is on. Save the backup codes.',
    'two_factor_disabled_success' => 'Two-step confirmation is off.',
    'two_factor_codes_regenerated' => 'New backup codes issued. The old ones no longer work.',
    'two_factor_codes_title' => 'Backup codes',
    'two_factor_codes_warning' => 'Save them somewhere safe. Each works once and they are shown only now — losing both the phone and these codes means losing the account.',
    'two_factor_codes_left' => 'Backup codes left',
    'two_factor_setup_intro' => 'A code from your phone will be asked in addition to the password. Even a stolen password will not be enough to sign in.',
    'two_factor_step_app' => 'Install an authenticator app — Google Authenticator, 1Password, Authy or any other.',
    'two_factor_step_secret' => 'Add the account to it: tap the link on a phone, or type the key by hand.',
    'two_factor_step_code' => 'Enter the code the app shows to make sure it works.',
    'two_factor_open_app' => 'Open in the authenticator app',
    'two_factor_enable_submit' => 'Turn on',
    'two_factor_disable_title' => 'Turn off',
    'two_factor_disable_hint' => 'The account will be protected by the password alone. Confirm with the current password.',
    'two_factor_disable_submit' => 'Turn off',
    'two_factor_regenerate_title' => 'Reissue backup codes',
    'two_factor_regenerate_hint' => 'The old codes stop working immediately.',
    'two_factor_regenerate_submit' => 'Reissue',
    'two_factor_link' => 'Two-step confirmation',
];
