<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomQuestion extends Model
{
    protected $fillable = [
        'category_id',
        'statement',
        'question_type',
    ];

    public function category()
    {
        return $this->belongsTo(EdomCategory::class);
    }
}
