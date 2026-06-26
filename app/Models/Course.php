<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $table = 'courses';

    protected $fillable = [
        'study_program_id',
        'name',
    ];

    public function prodi()
    {
        return $this->belongsTo(ProgramStudi::class, 'study_program_id');
    }

    public function edoms()
    {
        return $this->belongsToMany(
            SettingsEdom::class,
            'edom_courses',
            'course_id',
            'edom_id'
        );
    }
}
