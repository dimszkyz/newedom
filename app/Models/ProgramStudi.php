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
        'id_unw_fakultas',
        'nama_fakultas',
        'page_slug_fakultas',
        'api_updated_at',
        'synced_at',
    ];

    protected $casts = [
        'id_unw_program_studi' => 'integer',
        'id_unw_fakultas' => 'integer',
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
        $jenjang = trim((string) ($this->jenjang_nama_singkat ?: $this->jenjang));
        $nama = trim((string) $this->nama);

        if ($jenjang !== '' && $nama !== '') {
            return $jenjang.' - '.$nama;
        }

        return $nama !== '' ? $nama : '-';
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
