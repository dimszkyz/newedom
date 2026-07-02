<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramStudi extends Model
{
    protected $table = 'program_studi';

    protected $fillable = [
        'id_unw_program_studi',
        'nama',
        'slug',
        'page_slug',
        'jenjang',
        'jenjang_nama_singkat',
        'unw_fakultas_id',
        'unw_fakultas_nama',
        'unw_fakultas_page_slug',
        'api_created_at',
        'api_updated_at',
        'synced_at',
    ];

    protected $casts = [
        'api_created_at' => 'datetime',
        'api_updated_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function getNameAttribute(): ?string
    {
        return $this->attributes['nama'] ?? null;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['nama'] = $value;
    }

    public function getDisplayNameAttribute(): string
    {
        $degree = trim((string) ($this->jenjang_nama_singkat ?: $this->jenjang));
        $name = trim((string) $this->nama);

        return trim($degree.' '.$name) ?: '-';
    }

    public function edomSettings()
    {
        return $this->belongsToMany(
            EdomSettings::class,
            'edom_settings_program_studi',
            'program_studi_id',
            'edom_setting_id'
        )->withTimestamps();
    }

    public function edoms()
    {
        return $this->edomSettings();
    }
}
