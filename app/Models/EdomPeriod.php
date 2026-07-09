<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
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
        static::saving(function (EdomPeriod $period): void {
            if ($period->year === null || $period->siakad_idsemester === null) {
                return;
            }

            $periodExists = self::query()
                ->where('year', (int) $period->year)
                ->where('siakad_idsemester', (int) $period->siakad_idsemester)
                ->when($period->exists, fn ($query) => $query->whereKeyNot($period->getKey()))
                ->exists();

            if (! $periodExists) {
                return;
            }

            throw ValidationException::withMessages([
                'year' => 'Kombinasi Tahun Ajaran dan Semester SIAKAD sudah ada. Pilih tahun ajaran atau semester lain.',
                'siakad_idsemester' => 'Kombinasi Tahun Ajaran dan Semester SIAKAD sudah ada. Pilih tahun ajaran atau semester lain.',
            ]);
        });

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
            default => 'Semester '.$this->siakad_idsemester,
        };
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->year.' / '.$this->semester_name;
    }
}
