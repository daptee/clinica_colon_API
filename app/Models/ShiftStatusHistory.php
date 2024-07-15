<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ShiftStatusHistory extends Model
{
    use HasFactory;

    protected $table = "shifts_status_history";

    public function shift(): HasOne
    {
        return $this->hasOne(Shift::class, 'id', 'id_shift');
    }
    
    public function status(): HasOne
    {
        return $this->hasOne(ShiftStatus::class, 'id', 'id_status');
    }
}
