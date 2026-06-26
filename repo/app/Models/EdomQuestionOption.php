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
        'sort_order',
    ];

    public function settingEdom()
    {
        return $this->belongsTo(SettingEdom::class, 'edom_setting_id');
    }
}
