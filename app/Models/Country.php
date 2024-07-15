<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function provinces(): HasMany
    {
        return $this->hasMany(Province::class, 'id_country');
    }
}
