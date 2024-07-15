<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SpecialtyProfessional extends Model
{
    use HasFactory;

    protected $table = "specialties_professional";

    protected $fillable = [
        'id_professional',
        'id_specialty',
        'color',
        'shift_duration',
    ];

    protected $hidden = [
        // 'id_professional',
        // 'id_specialty',
        'deleted_at'
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'id_professional');
    }

    public function specialty(): HasOne
    {
        return $this->hasOne(Specialty::class, 'id', 'id_specialty');
    }
}
