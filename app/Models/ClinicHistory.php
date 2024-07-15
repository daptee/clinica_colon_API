<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClinicHistory extends Model
{
    use HasFactory;

    protected $table = "clinic_history";

    protected $fillable = [
        'id_patient',
        'id_professional',
        'datetime',
        'observations'
    ];

    protected $hidden = ['id_patient', 'id_professional', 'created_at', 'updated_at'];

    public static function getAllData($id)
    {
        return ClinicHistory::with(['professional:id,name,last_name,profile_picture,data', 'files'])->find($id);
    }

    public function professional(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'id_professional');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ClinicHistoryFile::class, 'id_clinic_history');
    }
}
