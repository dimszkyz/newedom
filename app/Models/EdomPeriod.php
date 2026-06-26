<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomPeriod extends Model
{
    protected $table = 'edom_periods';

    protected $fillable = [
        'year',
        'siakad_idsemester',
        'semester_name',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function responses()
    {
        return $this->hasMany(EdomResponse::class, 'edom_period_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
