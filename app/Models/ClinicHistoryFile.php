<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClinicHistoryFile extends Model
{
    use HasFactory;

    protected $table = "clinic_history_files";

    protected $fillable = [
        'id_clinic_history',
        'url',
    ];

    protected $hidden = ['created_at', 'updated_at'];

}
