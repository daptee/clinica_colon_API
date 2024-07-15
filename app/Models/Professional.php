<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Professional extends Model
{
    use HasFactory;

    protected $table = "professionals";

    protected $fillable = [
        'id_user_admin',
        'id_profesional',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'id_user_admin');
    }
}
