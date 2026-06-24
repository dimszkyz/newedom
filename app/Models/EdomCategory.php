<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomCategory extends Model
{
    protected $fillable = [
        'edom_id',
        'category_name',
    ];

    public function edom()
    {
        return $this->belongsTo(Edom::class);
    }

    public function questions()
    {
        return $this->hasMany(EdomQuestion::class, 'category_id');
    }
}
