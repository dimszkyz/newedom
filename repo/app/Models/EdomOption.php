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
        return $this->belongsTo(SettingsEdom::class, 'edom_setting_id');
    }
}
