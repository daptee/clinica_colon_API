<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Bienvenido a {{ config('services.clinic_name') }}</title>
</head>
<body>
    <p style="white-space: pre-line">
        Se le notifica que el usuario administrador de la plataforma de {{ config('services.clinic_name') }} le ha modificado su contraseña.

        La nueva contraseña es: {{ $new_password }}

        Muchas gracias.
    </p>
</body>
</html>