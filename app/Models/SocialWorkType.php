<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SocialWorkType extends Model
{
    use HasFactory;

    protected $table = "social_works_types";

    protected $fillable = [
        'name',
    ];

    protected $hidden = ['id', 'created_at', 'updated_at'];
}
