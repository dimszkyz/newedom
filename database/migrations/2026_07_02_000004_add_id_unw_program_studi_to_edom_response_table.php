<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('edom_response', 'id_unw_program_studi')) {
            Schema::table('edom_response', function (Blueprint $table) {
                $table->unsignedBigInteger('id_unw_program_studi')
                    ->nullable()
                    ->after('siakad_idtawarmatakuliahdetail');

                $table->index('id_unw_program_studi', 'edom_response_id_unw_program_studi_index');
            });
        }

        $this->backfillFromCachedKrsSections();
    }

    public function down(): void
    {
        if (! Schema::hasColumn('edom_response', 'id_unw_program_studi')) {
            return;
        }

        Schema::table('edom_response', function (Blueprint $table) {
            $table->dropIndex('edom_response_id_unw_program_studi_index');
            $table->dropColumn('id_unw_program_studi');
        });
    }

    private function backfillFromCachedKrsSections(): void
    {
        if (! Schema::hasTable('edom_krs_sections') || ! Schema::hasTable('edom_periods')) {
            return;
        }

        DB::table('edom_response')
            ->whereNull('id_unw_program_studi')
            ->select([
                'id',
                'edom_period_id',
                'siakad_idmahasiswa',
                'siakad_idmatakuliah',
                'siakad_idtawarmatakuliahdetail',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($responses): void {
                foreach ($responses as $response) {
                    $period = DB::table('edom_periods')
                        ->where('id', $response->edom_period_id)
                        ->first(['year', 'siakad_idsemester']);

                    if (! $period) {
                        continue;
                    }

                    $detailId = (int) $response->siakad_idtawarmatakuliahdetail;

                    $programStudiId = DB::table('edom_krs_sections')
                        ->where('siakad_idmahasiswa', (string) $response->siakad_idmahasiswa)
                        ->where('siakad_idtahunajaran', (int) $period->year)
                        ->where('siakad_idsemester', (int) $period->siakad_idsemester)
                        ->where('idmatakuliah', (int) $response->siakad_idmatakuliah)
                        ->whereNotNull('id_unw_program_studi')
                        ->when($detailId > 0, fn ($query) => $query->orderByRaw(
                            'CASE WHEN idtawarmatakuliahdetail = ? THEN 0 ELSE 1 END',
                            [$detailId]
                        ))
                        ->value('id_unw_program_studi');

                    if ($programStudiId === null) {
                        continue;
                    }

                    DB::table('edom_response')
                        ->where('id', $response->id)
                        ->update(['id_unw_program_studi' => (int) $programStudiId]);
                }
            });
    }
};
