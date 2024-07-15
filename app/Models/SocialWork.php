<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SocialWork extends Model
{
    use HasFactory;

    protected $table = "social_works";

    protected $fillable = [
        'id_type_social_work',
        'name',
    ];

    protected $hidden = ['id_type_social_work', 'created_at', 'updated_at'];

    public function type(): HasOne
    {
        return $this->hasOne(SocialWorkType::class, 'id', 'id_type_social_work');
    }
}
