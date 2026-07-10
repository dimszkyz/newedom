<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomSettings extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    protected $table = 'edom_settings';

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
    ];

    protected $fillable = [
        'name',
        'status',
    ];

    public function getEdomNameAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function setEdomNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }

    public function programStudis()
    {
        return $this->belongsToMany(
            ProgramStudi::class,
            'edom_settings_program_studi',
            'edom_setting_id',
            'program_studi_id'
        )->withTimestamps();
    }

    public function prodis()
    {
        return $this->programStudis();
    }

    public function categories()
    {
        return $this->hasMany(EdomQuestionCategory::class, 'edom_setting_id');
    }

    public function questions()
    {
        return $this->hasMany(EdomQuestion::class, 'edom_setting_id');
    }

    public function questionOptions()
    {
        return $this->hasMany(EdomQuestionOption::class, 'edom_setting_id');
    }

    public function options()
    {
        return $this->questionOptions();
    }

    public function responses()
    {
        return $this->hasMany(EdomResponse::class, 'edom_setting_id');
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => 'Aktif',
            self::STATUS_CLOSED => 'Ditutup',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function hasResponses(): bool
    {
        return $this->responses()->exists();
    }

    public function canModifyQuestionMaster(): bool
    {
        return $this->isDraft() && ! $this->hasResponses();
    }

    public function questionMasterLockLabel(): string
    {
        if ($this->hasResponses()) {
            return 'Sudah ada response mahasiswa';
        }

        if (! $this->isDraft()) {
            return 'Status Aktif atau Ditutup';
        }

        return 'Bisa diubah';
    }
}
