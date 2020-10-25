<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Авторизация</title>
    <meta name="author" content="">
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="app/components/css/normalize.css" rel="stylesheet">
    <link href="app/components/css/skeleton.min.css" rel="stylesheet">

    <link rel="apple-touch-icon" sizes="76x76" href="app/themes/default/assets/img/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="app/themes/default/assets/img/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="app/themes/default/assets/img/favicon/favicon-16x16.png">
    <link rel="manifest" href="app/themes/default/assets/img/favicon/site.webmanifest">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">

    <style>
        .container {
            margin-top: 30px;
        }
    </style>
</head>

<body>

<div class="container">
    <form>
        <input class="u-full-width" type="text" placeholder="Email / Имя пользователя" id="Username">
        <input class="u-full-width" type="password" placeholder="Пароль" id="Password">
        <input class="button-primary" type="submit" value="Вход">
    </form>
</div>
<script src="app/components/js/jquery-2.1.3.min.js"></script>
<script>
    $( document ).ready(function() {
        $('form').submit(function(e) {
            e.preventDefault();
            $.ajax({
                xhrFields: {
                    withCredentials: true
                },
                headers: {
                    'Authorization': 'Basic ' + btoa($('#Username').val() + ':' + $('#Password').val())
                },
                type: "POST",
                url: "signin",
                success: function(data)
                {
                    location.reload();
                },
                error: function (e) {

                }
            });
        });
    });
</script>
</body>

</html>