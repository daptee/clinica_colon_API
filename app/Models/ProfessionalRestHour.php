<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProfessionalRestHour extends Model
{
    use HasFactory;

    protected $table = "professional_rest_hours";

    protected $fillable = [
        'id_professional_schedule',
        'start_time',
        'end_time',
    ];

    protected $hidden = ['id_professional_schedule', 'created_at', 'updated_at'];
}
