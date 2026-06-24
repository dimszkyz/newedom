<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomOption extends Model
{
    protected $table = 'edom_question_options';

    protected $fillable = [
        'edom_setting_id',
        'name',
        'score',
        'sort_order',
    ];

    public function getLabelAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function setLabelAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }

    public function getNilaiAttribute(): mixed
    {
        return $this->attributes['score'] ?? null;
    }

    public function setNilaiAttribute(mixed $value): void
    {
        $this->attributes['score'] = $value;
    }

    public function getUrutanAttribute(): mixed
    {
        return $this->attributes['sort_order'] ?? null;
    }

    public function setUrutanAttribute(mixed $value): void
    {
        $this->attributes['sort_order'] = $value;
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
}
