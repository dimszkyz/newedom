<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class EdomPeriod extends Model
{
    protected $table = 'edom_periods';

    protected $fillable = [
        'year',
        'siakad_idsemester',
        'is_open_in_siakad',
        'allows_response_updates',
    ];

    protected $casts = [
        'is_open_in_siakad' => 'boolean',
        'allows_response_updates' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (EdomPeriod $period): void {
            if ($period->responses()->exists()) {
                throw new LogicException('Periode EDOM yang sudah memiliki respons tidak dapat dihapus.');
            }
        });
    }

    public function responses()
    {
        return $this->hasMany(EdomResponse::class, 'edom_period_id');
    }

    public function isOpenInSiakad(): bool
    {
        return (bool) $this->is_open_in_siakad;
    }

    public function allowsResponseUpdates(): bool
    {
        return $this->isOpenInSiakad() && (bool) $this->allows_response_updates;
    }

    public function locksResponseUpdates(): bool
    {
        return ! $this->allowsResponseUpdates();
    }

    public function settings()
    {
        return $this->belongsToMany(
            EdomSettings::class,
            'edom_period_edom_setting',
            'edom_period_id',
            'edom_setting_id',
        )->withTimestamps();
    }

    public function markAsOpenInSiakad(): void
    {
        $this->update([
            'is_open_in_siakad' => true,
            'allows_response_updates' => true,
        ]);
    }

    public function markAsClosedInSiakad(): void
    {
        $this->update([
            'is_open_in_siakad' => false,
            'allows_response_updates' => false,
        ]);
    }

    public function lockResponseUpdates(): void
    {
        $this->update(['allows_response_updates' => false]);
    }

    public function unlockResponseUpdates(): void
    {
        if (! $this->isOpenInSiakad()) {
            return;
        }

        $this->update(['allows_response_updates' => true]);
    }

    public function getLifecycleStatusAttribute(): string
    {
        if (! $this->isOpenInSiakad()) {
            return 'Tertutup di SIAKAD';
        }

        return $this->allowsResponseUpdates()
            ? 'Terbuka'
            : 'Pembaruan Dikunci';
    }

    public function getSemesterNameAttribute(): string
    {
        return match ((int) $this->siakad_idsemester) {
            1 => 'Gasal',
            2 => 'Genap',
            3 => 'Antara',
            default => 'Semester ' . $this->siakad_idsemester,
        };
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->year . ' / ' . $this->semester_name;
    }
}
