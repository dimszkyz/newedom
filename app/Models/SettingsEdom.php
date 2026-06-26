<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingsEdom extends Model
{
    protected $table = 'edom_settings';

    protected $fillable = [
        'name',
        'created_date',
        'status',
    ];

    public function getEdomNameAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function setEdomNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }

    public function prodis()
    {
        return $this->belongsToMany(
            Prodi::class,
            'edom_settings_program_studi',
            'edom_setting_id',
            'program_studi_id'
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
        return $this->hasMany(EdomCategory::class, 'edom_setting_id');
    }

    public function questions()
    {
        return $this->hasManyThrough(
            EdomQuestion::class,
            EdomCategory::class,
            'edom_setting_id',
            'edom_question_category_id',
            'id',
            'id'
        );
    }

    public function options()
    {
        return $this->hasMany(EdomOption::class, 'edom_setting_id');
    }

    public function responses()
    {
        return $this->hasMany(EdomResponse::class, 'edom_id');
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
}
