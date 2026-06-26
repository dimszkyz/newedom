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
        return trim((string) ($this->nama ?? ''));
    }

    public function settingEdoms()
    {
        return $this->belongsToMany(
            SettingEdom::class,
            'edom_settings_program_studi',
            'program_studi_id',
            'edom_setting_id'
        );
    }
}
