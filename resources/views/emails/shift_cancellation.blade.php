<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Cancelación de turno</title>
</head>
<body>
    <p style="white-space: pre-line">
        Hola {{ $shift->patient->name }}!

        Lamentamos informarte que se ha cancelado el turno que tenias agendado para el dia {{ $date }} a las {{ $shift->time }} con el profesional {{ $shift->professional->name }}, en la sucursal {{ $shift->branch_office->name }} de {{ $clinic_name }}.

        @if(isset($text))
        Descripcion de la cancelacion:
        {{ $text }}
        @endif

        Si necesitas reprogramar el turno no dudes en contactarnos.
        Saludos cordiales.
    </p>
</body>
</html>