<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Province extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_country',
        'name',
    ];

    protected $hidden = ['id_country', 'created_at', 'updated_at'];

    public function country(): HasOne
    {
        return $this->hasOne(Country::class, 'id', 'id_country');
    }
}
