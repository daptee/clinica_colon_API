<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Specialty extends Model
{
    use HasFactory;

    protected $table = "specialties";

    protected $fillable = [
        'name',
    ];

    protected $hidden = ['created_at', 'updated_at'];

}
