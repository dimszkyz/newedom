<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingEdom extends Model
{
    protected $table = 'edom_settings';

    protected $fillable = [
        'name',
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

    public function programStudis()
    {
        return $this->belongsToMany(
            ProgramStudi::class,
            'edom_settings_program_studi',
            'edom_setting_id',
            'program_studi_id'
        )->withTimestamps();
    }

    public function prodis()
    {
        return $this->programStudis();
    }

    public function categories()
    {
        return $this->hasMany(EdomQuestionCategory::class, 'edom_setting_id');
    }

    public function questions()
    {
        return $this->hasMany(EdomQuestion::class, 'edom_setting_id');
    }

    public function questionOptions()
    {
        return $this->hasMany(EdomQuestionOption::class, 'edom_setting_id');
    }

    public function options()
    {
        return $this->questionOptions();
    }

    public function responses()
    {
        return $this->hasMany(EdomResponse::class, 'edom_setting_id');
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
