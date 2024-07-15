<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Confirmación de turno</title>
</head>
<body>
    <p style="white-space: pre-line">
        Hola {{ $shift->patient->name }}!

        Queremos avisarte que el turno que has solicitado para el dia {{ $date }} a las {{ $shift->time }} ha sido {{ $status }}. @if($status == "Reprogramado") El nuevo turno es para el día $nuevaFecha a las $nuevaHora. @endif

        Si necesitas cancelarlo o reprogramarlo, podes realizarlo a cualquiera de nuestros canales de contacto con 24hs de anticipacion.

        Desde ya, muchas gracias.
    </p>
</body>
</html>