<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomQuestion extends Model
{
    protected $table = 'edom_questions';

    protected $fillable = [
        'edom_question_category_id',
        'statement',
        'question_type',
    ];

    public function category()
    {
        return $this->belongsTo(EdomQuestionCategory::class, 'edom_question_category_id');
    }

    public function responseDetails()
    {
        return $this->hasMany(EdomResponseDetail::class, 'edom_question_id');
    }
}
