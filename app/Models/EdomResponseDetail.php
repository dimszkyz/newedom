<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomResponseDetail extends Model
{
    protected $table = 'edom_response_detail';

    protected $fillable = [
        'edom_response_id',
        'edom_question_id',
        'edom_option_id',
        'answer_text',
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
        return $this->belongsTo(EdomQuestionOption::class, 'edom_option_id');
    }

    public function option()
    {
        return $this->questionOption();
    }
}
