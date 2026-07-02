<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('edom_krs_sections')) {
            return;
        }

        Schema::create('edom_krs_sections', function (Blueprint $table) {
            $table->id();
            $table->string('siakad_idmahasiswa');
            $table->unsignedInteger('siakad_idtahunajaran');
            $table->unsignedInteger('siakad_idsemester');
            $table->unsignedBigInteger('idtawarmatakuliahdetail')->nullable();
            $table->unsignedBigInteger('idmatakuliah');
            $table->string('kode')->nullable();
            $table->string('nama');
            $table->string('dosen_nidn')->nullable();
            $table->string('dosen_nama')->nullable();
            $table->json('dosen_team')->nullable();
            $table->unsignedBigInteger('id_unw_program_studi')->nullable()->index();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->unique([
                'siakad_idmahasiswa',
                'siakad_idtahunajaran',
                'siakad_idsemester',
                'idtawarmatakuliahdetail',
                'idmatakuliah',
            ], 'edom_krs_sections_unique_section');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_krs_sections');
    }
};
