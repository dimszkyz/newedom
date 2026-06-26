<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomQuestionOption extends Model
{
    protected $table = 'edom_question_options';

    protected $fillable = [
        'edom_setting_id',
        'name',
        'score',
    ];

    public function getLabelAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function setLabelAttribute(?string $value): void
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

    public function responseDetails()
    {
        return $this->hasMany(EdomResponseDetail::class, 'edom_option_id');
    }
}
