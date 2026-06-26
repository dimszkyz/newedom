<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomResponseDetail extends Model
{
    protected $table = 'edom_response_detail';

    protected $fillable = [
        'edom_response_id',
        'edom_question_id',
        'edom_question_option_id',
        'category_name_snapshot',
        'statement_snapshot',
        'option_label_snapshot',
        'option_score_snapshot',
        'answer_text',
        'score',
    ];

    public function response()
    {
        return $this->belongsTo(EdomResponse::class, 'edom_response_id');
    }

    public function question()
    {
        return $this->belongsTo(EdomQuestion::class, 'edom_question_id');
    }

    public function questionOption()
    {
        return $this->belongsTo(EdomQuestionOption::class, 'edom_question_option_id');
    }

    public function option()
    {
        return $this->questionOption();
    }
}
