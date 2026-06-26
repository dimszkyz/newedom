<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prodi extends Model
{
    protected $table = 'program_studi';

    protected $fillable = [
        'id_unw_program_studi',
        'nama',
    ];

    public function getDisplayNameAttribute(): string
    {
        return trim((string) ($this->nama ?? $this->attributes['nama'] ?? ''));
    }

    public function getNameAttribute(): ?string
    {
        return $this->attributes['nama'] ?? null;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['nama'] = $value;
    }

    public function getUnwStudyProgramIdAttribute(): mixed
    {
        return $this->attributes['id_unw_program_studi'] ?? null;
    }

    public function setUnwStudyProgramIdAttribute(mixed $value): void
    {
        $this->attributes['id_unw_program_studi'] = $value;
    }

    public function edoms()
    {
        return $this->belongsToMany(
            Edom::class,
            'edom_settings_program_studi',
            'program_studi_id',
            'edom_setting_id'
        )->withTimestamps();
    }
}
