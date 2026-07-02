<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdomResponse extends Model
{
    protected $table = 'edom_response';

    protected $fillable = [
        'edom_period_id',
        'edom_setting_id',
        'siakad_idmahasiswa',
        'siakad_idmatakuliah',
        'siakad_idtawarmatakuliahdetail',
        'id_unw_program_studi',
        'submitted_at',
    ];

    protected $casts = [
        'id_unw_program_studi' => 'integer',
        'submitted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (EdomResponse $response): void {
            if ($response->id_unw_program_studi !== null) {
                return;
            }

            $response->id_unw_program_studi = $response->resolveProgramStudiIdFromKrsSection();
        });
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(EdomPeriod::class, 'edom_period_id');
    }

    public function edomSettings(): BelongsTo
    {
        return $this->belongsTo(EdomSettings::class, 'edom_setting_id');
    }

    public function edom(): BelongsTo
    {
        return $this->edomSettings();
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'id_unw_program_studi', 'id_unw_program_studi');
    }

    public function details()
    {
        return $this->hasMany(EdomResponseDetail::class, 'edom_response_id');
    }

    public function answers()
    {
        return $this->details();
    }

    private function resolveProgramStudiIdFromKrsSection(): ?int
    {
        $period = $this->relationLoaded('period') ? $this->period : null;

        if (! $period && $this->edom_period_id) {
            $period = EdomPeriod::query()->find($this->edom_period_id);
        }

        if (! $period || $this->siakad_idmahasiswa === null || $this->siakad_idmatakuliah === null) {
            return null;
        }

        $query = EdomKrsSection::query()
            ->where('siakad_idmahasiswa', (string) $this->siakad_idmahasiswa)
            ->where('siakad_idtahunajaran', (int) $period->year)
            ->where('siakad_idsemester', (int) $period->siakad_idsemester)
            ->where('idmatakuliah', (int) $this->siakad_idmatakuliah)
            ->whereNotNull('id_unw_program_studi');

        $detailId = (int) $this->siakad_idtawarmatakuliahdetail;

        if ($detailId > 0) {
            $query->orderByRaw(
                'CASE WHEN idtawarmatakuliahdetail = ? THEN 0 ELSE 1 END',
                [$detailId]
            );
        }

        $programStudiId = $query->value('id_unw_program_studi');

        return $programStudiId === null ? null : (int) $programStudiId;
    }
}
