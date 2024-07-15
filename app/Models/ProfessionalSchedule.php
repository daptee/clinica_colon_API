<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProfessionalSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_professional',
        'day',
        'start_time',
        'end_time',
        'id_branch_office',
    ];

    protected $hidden = ['id_professional', 'created_at', 'updated_at'];

    public function professional(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'id_professional');
    }

    public function rest_hours(): HasMany
    {
        return $this->hasMany(ProfessionalRestHour::class, 'id_professional_schedule');
    }

    public function branch_office(): HasOne
    {
        return $this->hasOne(BranchOffice::class, 'id', 'id_branch_office');
    }
    
}
