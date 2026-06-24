<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\EdomCategory;
use App\Models\EdomQuestion;

class Edom extends Model
{
    protected $fillable = [
        'edom_name',
        'created_date',
        'status',
    ];

    public function prodis()
    {
        return $this->belongsToMany(
            Prodi::class,
            'edom_study_programs',
            'edom_id',
            'study_program_id'
        )->withTimestamps();
    }

    public function mataKuliahs()
    {
        return $this->belongsToMany(
            MataKuliah::class,
            'edom_courses',
            'edom_id',
            'course_id'
        );
    }

    public function categories()
    {
        return $this->hasMany(EdomCategory::class);
    }

    public function questions()
    {
        return $this->hasManyThrough(
            EdomQuestion::class,
            EdomCategory::class,
            'edom_id',
            'category_id'
        );
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function options()
    {
        return $this->hasMany(EdomOption::class);
    }

    public function responses()
    {
        return $this->hasMany(EdomResponse::class);
    }
}
