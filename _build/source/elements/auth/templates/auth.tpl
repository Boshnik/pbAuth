<!doctype html>
<html lang="en">
<head>
    <title>{$title} | {'site_name'|config}</title>
    <meta charset="{'modx_charset'|config}">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <base href="{'site_url'|config}"/>

    {*bootstrap*}
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script async src="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<section class="section-auth">
    <div class="container">
        {block 'content'}
            <div class="row">
                <div class="col-12 col-md-5 mx-auto my-5">
                    {set $chunkPath = 'file:auth/chunks/' ~ $form}
                    {include $chunkPath}
                </div>
            </div>
        {/block}
    </div>
</section>

</body>
</html>