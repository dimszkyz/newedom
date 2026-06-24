<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MataKuliah extends Model
{
    protected $table = 'courses';

    protected $fillable = [
        'study_program_id',
        'name',
    ];

    public function prodi()
    {
        return $this->belongsTo(Prodi::class, 'study_program_id');
    }

    public function edoms()
    {
        return $this->belongsToMany(
            Edom::class,
            'edom_courses',
            'course_id',
            'edom_id'
        );
    }
}
