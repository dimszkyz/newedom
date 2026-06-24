<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomResponse extends Model
{
    protected $fillable = [
        'edom_id',
        'edom_name_snapshot',
        'study_program_snapshot',
        'course_snapshot',
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
