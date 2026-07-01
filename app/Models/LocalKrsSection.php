<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocalKrsSection extends Model
{
    protected $table = 'local_krs_sections';

    protected $fillable = [
        'siakad_idmahasiswa',
        'siakad_idtahunajaran',
        'siakad_idsemester',
        'id_unw_program_studi',
        'idtawarmatakuliahdetail',
        'idmatakuliah',
        'kode',
        'nama',
        'dosen_nidn',
        'dosen_nama',
        'dosen_team',
    ];

    protected $casts = [
        'siakad_idtahunajaran' => 'integer',
        'siakad_idsemester' => 'integer',
        'id_unw_program_studi' => 'integer',
        'idtawarmatakuliahdetail' => 'integer',
        'idmatakuliah' => 'integer',
        'dosen_team' => 'array',
    ];

    public function toSiakadSection(): array
    {
        return [
            'idtawarmatakuliahdetail' => $this->idtawarmatakuliahdetail,
            'idmatakuliah' => $this->idmatakuliah,
            'kode' => $this->kode,
            'nama' => $this->nama,
            'dosen' => [
                'nidn' => $this->dosen_nidn,
                'nama' => $this->dosen_nama,
            ],
            'dosen_team' => is_array($this->dosen_team) ? $this->dosen_team : [],
            'id_unw_program_studi' => $this->id_unw_program_studi,
        ];
    }
}
