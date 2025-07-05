<html>
    <body>
    {switch $modx->config.cultureKey}
        {case 'ru'}
            <p>Здравствуйте, {$username}!</p>
            <p>Для сброса пароля перейдите по ссылке:</p>
            <p><a href="{$link}">{$link}</a></p>
            <p>Ссылка действительна в течение 1 часа.</p>
            <p>Если вы не запрашивали сброс пароля, просто проигнорируйте это письмо.</p>
        {case 'de'}
            <p>Hallo, {$username}!</p>
            <p>Um Ihr Passwort zurückzusetzen, klicken Sie bitte auf den folgenden Link:</p>
            <p><a href="{$link}">{$link}</a></p>
            <p>Der Link ist 1 Stunde lang gültig.</p>
            <p>Wenn Sie keine Zurücksetzung des Passworts angefordert haben, ignorieren Sie bitte diese E-Mail.</p>
        {case 'uk'}
            <p>Вітаємо, {$username}!</p>
            <p>Щоб скинути пароль, перейдіть за посиланням:</p>
            <p><a href="{$link}">{$link}</a></p>
            <p>Посилання дійсне протягом 1 години.</p>
            <p>Якщо ви не надсилали запит на скидання пароля, просто проігноруйте цей лист.</p>
        {default}
            <p>Hello, {$username}!</p>
            <p>To reset your password, please follow the link below:</p>
            <p><a href="{$link}">{$link}</a></p>
            <p>This link is valid for 1 hour.</p>
            <p>If you did not request a password reset, please ignore this email.</p>
    {/switch}
    </body>
</html>