<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomPeriod extends Model
{
    protected $table = 'edom_periods';

    protected $fillable = [
        'year',
        'siakad_idsemester',
        'is_open_in_siakad',
    ];

    protected $casts = [
        'is_open_in_siakad' => 'boolean',
    ];

    public function responses()
    {
        return $this->hasMany(EdomResponse::class, 'edom_period_id');
    }

    public function isOpenInSiakad(): bool
    {
        return (bool) $this->is_open_in_siakad;
    }

    public function locksResponseUpdates(): bool
    {
        return ! $this->isOpenInSiakad();
    }

    public function markAsOpenInSiakad(): void
    {
        $this->update(['is_open_in_siakad' => true]);
    }

    public function markAsClosedInSiakad(): void
    {
        $this->update(['is_open_in_siakad' => false]);
    }
}
