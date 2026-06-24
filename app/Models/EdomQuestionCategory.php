<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomCategory extends Model
{
    protected $table = 'edom_question_categories';

    protected $fillable = [
        'edom_setting_id',
        'name',
    ];

    public function getCategoryNameAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function setCategoryNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }

    public function getEdomIdAttribute(): mixed
    {
        return $this->attributes['edom_setting_id'] ?? null;
    }

    public function setEdomIdAttribute(mixed $value): void
    {
        $this->attributes['edom_setting_id'] = $value;
    }

    public function edom()
    {
        return $this->belongsTo(Edom::class, 'edom_setting_id');
    }

    public function questions()
    {
        return $this->hasMany(EdomQuestion::class, 'edom_question_category_id');
    }
}
