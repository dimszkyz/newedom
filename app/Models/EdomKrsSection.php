<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomKrsSection extends Model
{
    protected $table = 'edom_krs_sections';

    protected $fillable = [
        'siakad_idmahasiswa',
        'siakad_idtahunajaran',
        'siakad_idsemester',
        'idtawarmatakuliahdetail',
        'idmatakuliah',
        'kode',
        'nama',
        'dosen_nidn',
        'dosen_nama',
        'dosen_team',
        'id_unw_program_studi',
        'fetched_at',
    ];

    protected $casts = [
        'siakad_idtahunajaran' => 'integer',
        'siakad_idsemester' => 'integer',
        'idtawarmatakuliahdetail' => 'integer',
        'idmatakuliah' => 'integer',
        'dosen_team' => 'array',
        'id_unw_program_studi' => 'integer',
        'fetched_at' => 'datetime',
    ];

    public function getCourseLabelAttribute(): string
    {
        return trim((string) $this->kode.' - '.(string) $this->nama, ' -') ?: 'Mata kuliah #'.$this->idmatakuliah;
    }
}
