<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id_patient',
        'id_professional',
        'date',
        'time',
        'id_branch_office',
        'overshift',
        'comments',
        'id_status',
        'id_specialty'
    ];

    protected $hidden = ['id_patient', 'id_professional', 'id_branch_office', 'id_status', 'updated_at', 'deleted_at'];

    protected $casts = [
        'overshift' => 'boolean',
    ];
    
    public function patient(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'id_patient');
    }
    
    public function professional(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'id_professional');
    }

    public function branch_office(): HasOne
    {
        return $this->hasOne(BranchOffice::class, 'id', 'id_branch_office');
    }

    public function status(): HasOne
    {
        return $this->hasOne(ShiftStatus::class, 'id', 'id_status');
    }

    public function specialty(): HasOne
    {
        return $this->hasOne(Specialty::class, 'id', 'id_specialty');
    }

    public static function getAllData($id)
    {
        return Shift::with(['patient', 'professional', 'branch_office', 'status'])->find($id);
    }
    
    public function specialties_professional(): HasMany
    {
        return $this->hasMany(SpecialtyProfessional::class, 'id_professional', 'id_professional');
    }
    
}
