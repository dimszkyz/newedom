<?php

namespace App\Console\Commands;

use App\Models\LocalKrsSection;
use Illuminate\Console\Command;

class UpsertLocalKrsSection extends Command
{
    protected $signature = 'edom:local-krs-upsert {--mahasiswa=18273} {--tahun=2026} {--semester=2} {--matakuliah=123} {--detail=4567} {--kode=LOCAL101} {--nama=Mata Kuliah Lokal} {--prodi=21} {--dosen=Dosen Lokal} {--nidn=} {--team=*}';

    protected $description = 'Tambah atau update data KRS lokal untuk fallback saat API SIAKAD gagal.';

    public function handle(): int
    {
        $detailId = trim((string) $this->option('detail'));
        $prodiId = trim((string) $this->option('prodi'));
        $team = collect($this->option('team') ?? [])
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->values()
            ->all();

        $section = LocalKrsSection::query()->updateOrCreate(
            [
                'siakad_idmahasiswa' => (string) $this->option('mahasiswa'),
                'siakad_idtahunajaran' => (int) $this->option('tahun'),
                'siakad_idsemester' => (int) $this->option('semester'),
                'idmatakuliah' => (int) $this->option('matakuliah'),
                'idtawarmatakuliahdetail' => $detailId === '' ? null : (int) $detailId,
            ],
            [
                'id_unw_program_studi' => $prodiId === '' ? null : (int) $prodiId,
                'kode' => (string) $this->option('kode'),
                'nama' => (string) $this->option('nama'),
                'dosen_nidn' => trim((string) $this->option('nidn')) ?: null,
                'dosen_nama' => trim((string) $this->option('dosen')) ?: null,
                'dosen_team' => $team,
            ]
        );

        $this->info('Data KRS lokal tersimpan.');
        $this->line('ID: '.$section->id);
        $this->line('Mahasiswa: '.$section->siakad_idmahasiswa);
        $this->line('Mata kuliah: '.($section->kode ?: '-').' - '.$section->nama);
        $this->line('Dosen: '.($section->dosen_nama ?: '-'));

        return self::SUCCESS;
    }
}
