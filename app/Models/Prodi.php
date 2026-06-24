<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prodi extends Model
{
    protected $table = 'study_programs';

    protected $fillable = [
        'unw_study_program_id',
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

    public function mataKuliahs()
    {
        return $this->hasMany(MataKuliah::class, 'study_program_id');
    }

    public function edoms()
    {
        return $this->belongsToMany(
            Edom::class,
            'edom_study_programs',
            'study_program_id',
            'edom_id'
        )->withTimestamps();
    }
}
