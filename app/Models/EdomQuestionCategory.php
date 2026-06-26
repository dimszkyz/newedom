<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomQuestionCategory extends Model
{
    protected $table = 'edom_question_categories';

    protected $fillable = [
        'edom_setting_id',
        'name',
    ];

    public function getCategoryNameAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function setCategoryNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }

    public function settingEdom()
    {
        return $this->belongsTo(SettingEdom::class, 'edom_setting_id');
    }

    public function edom()
    {
        return $this->settingEdom();
    }

    public function questions()
    {
        return $this->hasMany(EdomQuestion::class, 'edom_question_category_id');
    }
}
