<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomQuestion extends Model
{
    protected $fillable = [
        'edom_question_category_id',
        'statement',
        'question_type',
    ];

    public function getCategoryIdAttribute(): mixed
    {
        return $this->attributes['edom_question_category_id'] ?? null;
    }

    public function setCategoryIdAttribute(mixed $value): void
    {
        $this->attributes['edom_question_category_id'] = $value;
    }

    public function category()
    {
        return $this->belongsTo(EdomQuestionCategory::class, 'edom_question_category_id');
    }
}
