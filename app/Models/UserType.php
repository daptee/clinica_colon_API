<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserType extends Model
{
    use HasFactory;

    const ADMIN = 1;
    const PROFESIONAL = 2;
    const PACIENTE = 3;

    protected $hidden = ['created_at', 'updated_at'];
}
