<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('edom_response')) {
            return;
        }

        if (! Schema::hasColumn('edom_response', 'id_unw_program_studi')) {
            Schema::table('edom_response', function (Blueprint $table) {
                $table->unsignedBigInteger('id_unw_program_studi')
                    ->nullable()
                    ->index()
                    ->after('siakad_idtawarmatakuliahdetail');
            });
        }

        if (
            ! Schema::hasTable('edom_periods')
            || ! Schema::hasTable('edom_krs_sections')
        ) {
            return;
        }

        DB::table('edom_response')
            ->whereNull('id_unw_program_studi')
            ->orderBy('id')
            ->chunkById(100, function ($responses): void {
                foreach ($responses as $response) {
                    $period = DB::table('edom_periods')->find($response->edom_period_id);

                    if ($period === null) {
                        continue;
                    }

                    $section = DB::table('edom_krs_sections')
                        ->where('siakad_idmahasiswa', (string) $response->siakad_idmahasiswa)
                        ->where('siakad_idtahunajaran', (int) $period->year)
                        ->where('siakad_idsemester', (int) $period->siakad_idsemester)
                        ->where('idmatakuliah', (int) $response->siakad_idmatakuliah)
                        ->whereNotNull('id_unw_program_studi')
                        ->orderByRaw(
                            'CASE WHEN idtawarmatakuliahdetail = ? THEN 0 ELSE 1 END',
                            [(int) $response->siakad_idtawarmatakuliahdetail],
                        )
                        ->first();

                    if ($section === null) {
                        continue;
                    }

                    DB::table('edom_response')
                        ->where('id', $response->id)
                        ->update([
                            'id_unw_program_studi' => (int) $section->id_unw_program_studi,
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (
            Schema::hasTable('edom_response')
            && Schema::hasColumn('edom_response', 'id_unw_program_studi')
        ) {
            Schema::table('edom_response', function (Blueprint $table) {
                $table->dropColumn('id_unw_program_studi');
            });
        }
    }
};
