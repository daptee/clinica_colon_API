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
        Se ha solicitado la recuperación de contraseña de su cuenta de {{ config('services.clinic_name') }}. Si usted no fue quien la realizo, por favor ingrese y modifique su contraseña, ya que alguien mas ha ingresado en su cuenta.

        En caso de que haya sido usted, le pedimos por favor ingrese al siguiente link para recuperar sus datos:

        {{ config('services.url_front') }}/generacion-password?{{ Crypt::encrypt($user->email) }}

        Muchas gracias.

        El equipo de {{ config('services.clinic_name') }}.
    </p>
</body>
</html>