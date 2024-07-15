<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserPlan extends Model
{
    use HasFactory;

    protected $table = "users_plans";

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id_user');
    }

    public function plan(): HasOne
    {
        return $this->hasOne(Plan::class, 'id_plan');
    }
}
