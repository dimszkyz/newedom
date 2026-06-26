<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('edom_responses')) {
            return;
        }

        Schema::table('edom_responses', function (Blueprint $table) {
            if (! Schema::hasColumn('edom_responses', 'siakad_idmahasiswa')) {
                $table->unsignedBigInteger('siakad_idmahasiswa')->nullable()->after('edom_id')->index();
            }

            if (! Schema::hasColumn('edom_responses', 'siakad_idtahunajaran')) {
                $table->unsignedBigInteger('siakad_idtahunajaran')->nullable()->after('siakad_idmahasiswa')->index();
            }

            if (! Schema::hasColumn('edom_responses', 'siakad_idsemester')) {
                $table->unsignedBigInteger('siakad_idsemester')->nullable()->after('siakad_idtahunajaran')->index();
            }

            if (! Schema::hasColumn('edom_responses', 'siakad_idmatakuliah')) {
                $table->unsignedBigInteger('siakad_idmatakuliah')->nullable()->after('siakad_idsemester')->index();
            }

            if (! Schema::hasColumn('edom_responses', 'siakad_idtawarmatakuliahdetail')) {
                $table->unsignedBigInteger('siakad_idtawarmatakuliahdetail')->nullable()->after('siakad_idmatakuliah')->index();
            }

            if (! Schema::hasColumn('edom_responses', 'id_unw_program_studi')) {
                $table->unsignedBigInteger('id_unw_program_studi')->nullable()->after('siakad_idtawarmatakuliahdetail')->index();
            }

            if (! Schema::hasColumn('edom_responses', 'lecturer_name_snapshot')) {
                $table->string('lecturer_name_snapshot')->nullable()->after('course_snapshot');
            }

            if (! Schema::hasColumn('edom_responses', 'lecturer_nidn_snapshot')) {
                $table->string('lecturer_nidn_snapshot')->nullable()->after('lecturer_name_snapshot');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('edom_responses')) {
            return;
        }

        Schema::table('edom_responses', function (Blueprint $table) {
            foreach ([
                'lecturer_nidn_snapshot',
                'lecturer_name_snapshot',
                'id_unw_program_studi',
                'siakad_idtawarmatakuliahdetail',
                'siakad_idmatakuliah',
                'siakad_idsemester',
                'siakad_idtahunajaran',
                'siakad_idmahasiswa',
            ] as $column) {
                if (Schema::hasColumn('edom_responses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
