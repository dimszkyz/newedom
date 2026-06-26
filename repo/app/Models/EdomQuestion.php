<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomQuestion extends Model
{
    protected $table = 'edom_questions';

    protected $fillable = [
        'edom_setting_id',
        'edom_question_category_id',
        'statement',
        'question_type',
    ];

    public function settingEdom()
    {
        return $this->belongsTo(SettingEdom::class, 'edom_setting_id');
    }

    public function category()
    {
        return $this->belongsTo(EdomQuestionCategory::class, 'edom_question_category_id');
    }

    public function isTextQuestion(): bool
    {
        return in_array(strtolower((string) $this->question_type), ['text', 'essay', 'esai', 'uraian', 'textarea'], true);
    }

    public function isOptionQuestion(): bool
    {
        return ! $this->isTextQuestion();
    }
}
