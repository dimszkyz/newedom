<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramStudi extends Model
{
    protected $table = 'program_studi';

    protected $fillable = [
        'id_unw_program_studi',
        'nama',
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
        return (string) ($this->nama ?? '-');
    }

    public function settingEdoms()
    {
        return $this->belongsToMany(
            SettingEdom::class,
            'edom_settings_program_studi',
            'program_studi_id',
            'edom_setting_id'
        )->withTimestamps();
    }

    public function edoms()
    {
        return $this->settingEdoms();
    }
}
