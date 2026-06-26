<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomPeriod extends Model
{
    protected $table = 'edom_periods';

    protected $fillable = [
        'year',
        'siakad_idsemester',
    ];

    public function responses()
    {
        return $this->hasMany(EdomResponse::class, 'edom_period_id');
    }
}
