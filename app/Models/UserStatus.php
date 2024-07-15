<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStatus extends Model
{
    use HasFactory;

    protected $table = "user_status";

    const ACTIVO = 1;
    const INACTIVO = 2;

    protected $hidden = ['created_at', 'updated_at'];
}
