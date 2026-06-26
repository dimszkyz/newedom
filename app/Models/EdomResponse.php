<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomResponse extends Model
{
    protected $fillable = [
        'edom_id',
        'siakad_idmahasiswa',
        'siakad_idtahunajaran',
        'siakad_idsemester',
        'siakad_idmatakuliah',
        'siakad_idtawarmatakuliahdetail',
        'id_unw_program_studi',
        'edom_name_snapshot',
        'study_program_snapshot',
        'course_snapshot',
        'lecturer_name_snapshot',
        'lecturer_nidn_snapshot',
        'respondent_name',
        'student_number',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function edom()
    {
        return $this->belongsTo(Edom::class);
    }

    public function answers()
    {
        return $this->hasMany(EdomAnswer::class);
    }
}
