<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PatientFile extends Model
{
    use HasFactory;

    protected $table = "patients_files";

    public function patient(): HasOne
    {
        return $this->hasOne(User::class, 'id_patient');
    }
}
