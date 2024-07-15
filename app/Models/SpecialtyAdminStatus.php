<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialtyAdminStatus extends Model
{
    use HasFactory;

    protected $table = "specialties_admin_status";

    const ACTIVO = 1;
    const INACTIVO = 2;

    protected $hidden = ['created_at', 'updated_at'];
}
