<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramStudi extends Model
{
    protected $table = 'program_studi';

    protected $fillable = [
        'id_unw_program_studi',
        'name',
        'slug',
        'page_slug',
        'degree_level',
        'degree_short_name',
        'unw_faculty_id',
        'faculty_name',
        'faculty_page_slug',
        'api_updated_at',
        'synced_at',
    ];

    protected $casts = [
        'api_updated_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function getDisplayNameAttribute(): string
    {
        return trim(($this->degree_short_name ? $this->degree_short_name.' - ' : '').$this->name);
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
