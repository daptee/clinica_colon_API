<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftStatus extends Model
{
    use HasFactory;

    protected $table = "shifts_status";

    const PENDIENTE = 1;
    const ACTIVO = 2;
    const PRESENTE = 3;
    const AUSENTE = 4;
    const ATENDIENDO = 5;
    const ATENDIDO = 6;
    const CANCELADO = 7;
    const REPROGRAMADO = 8;

    protected $hidden = ['created_at', 'updated_at'];
}
