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
        Hola {{ $shift->patient->name . ' ' $shift->patient->last_name }}!

        Queremos avisarte que el turno que has solicitado para el dia {{ $date }} a las {{ $shift->time }} se encuentra confirmado. El mismo es con el profesional {{ $shift->professional->name . ' ' $shift->professional->last_name }} en la sucursal {{ $shift->branch_office->name }} {{ $shift->branch_office->address }}.

        Si necesitas cancelarlo o reprogramarlo, podes realizarlo a cualquiera de nuestros canales de contacto con 24hs de anticipacion.

        Desde ya, muchas gracias.
    </p>
</body>
</html>