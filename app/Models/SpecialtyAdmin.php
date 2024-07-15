<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SpecialtyAdmin extends Model
{
    use HasFactory;

    protected $table = "specialties_admin";

    protected $fillable = [
        'id_user',
        'id_specialty',
        'color',
        'shift_duration',
        'id_status'
    ];

    protected $hidden = [
        'id_user',
        'id_specialty',
        'id_status',
        'deleted_at'
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'id_user');
    }

    public function specialty(): HasOne
    {
        return $this->hasOne(Specialty::class, 'id', 'id_specialty');
    }

    public function status(): HasOne
    {
        return $this->hasOne(SpecialtyAdminStatus::class, 'id', 'id_status');
    }
}
