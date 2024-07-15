<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Patient extends Model
{
    use HasFactory;

    protected $table = "patients";

    protected $fillable = [
        'id_user',
        'id_patient',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'id_user');
    }
}
