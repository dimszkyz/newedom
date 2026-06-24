<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prodi extends Model
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
        return trim(($this->degree_short_name ? $this->degree_short_name . ' - ' : '') . $this->name);
    }

    public function getUnwStudyProgramIdAttribute(): mixed
    {
        return $this->attributes['id_unw_program_studi'] ?? null;
    }

    public function setUnwStudyProgramIdAttribute(mixed $value): void
    {
        $this->attributes['id_unw_program_studi'] = $value;
    }

    public function mataKuliahs()
    {
        return $this->hasMany(MataKuliah::class, 'study_program_id');
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
