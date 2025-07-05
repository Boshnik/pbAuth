<html>
<body>
{switch $modx->config.cultureKey}
{case 'ru'}
    <p>Здравствуйте, {$username}!</p>
    <p>Пожалуйста, подтвердите ваш email, перейдя по ссылке:</p>
    <p><a href="{$link}">{$link}</a></p>
    <p>Если вы не регистрировались на нашем сайте, просто проигнорируйте это письмо.</p>
{case 'de'}
    <p>Hallo, {$username}!</p>
    <p>Bitte bestätigen Sie Ihre E-Mail-Adresse, indem Sie auf den folgenden Link klicken:</p>
    <p><a href="{$link}">{$link}</a></p>
    <p>Wenn Sie sich nicht auf unserer Website registriert haben, ignorieren Sie bitte diese E-Mail.</p>
{case 'uk'}
    <p>Вітаємо, {$username}!</p>
    <p>Будь ласка, підтвердьте свою електронну адресу, перейшовши за посиланням:</p>
    <p><a href="{$link}">{$link}</a></p>
    <p>Якщо ви не реєструвалися на нашому сайті, просто проігноруйте цей лист.</p>
{default}
    <p>Hello, {$username}!</p>
    <p>Please confirm your email address by clicking the link below:</p>
    <p><a href="{$link}">{$link}</a></p>
    <p>If you did not register on our website, please ignore this email.</p>
{/switch}
</body>
</html>