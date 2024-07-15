<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProfessionalSpecialDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_professional',
        'complete_day',
        'date',
        'start_time',
        'end_time',
        'comment',
    ];

    protected $hidden = ['id_professional', 'created_at', 'updated_at'];

    public function professional(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'id_professional');
    }
}
