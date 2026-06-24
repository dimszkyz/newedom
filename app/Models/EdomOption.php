<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomOption extends Model
{
    protected $fillable = [
        'edom_id',
        'label',
        'score',
        'sort_order',
    ];

    public function edom()
    {
        return $this->belongsTo(Edom::class);
    }
}
