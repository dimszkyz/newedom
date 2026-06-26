<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('edom_response')) {
            return;
        }

        Schema::create('edom_response', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edom_period_id')->nullable()->constrained('edom_periods')->nullOnDelete();
            $table->foreignId('edom_setting_id')->nullable()->constrained('edom_settings')->nullOnDelete();
            $table->string('siakad_idmahasiswa')->nullable();
            $table->unsignedInteger('siakad_idtahunajaran')->nullable();
            $table->unsignedInteger('siakad_idsemester')->nullable();
            $table->unsignedBigInteger('siakad_idmatakuliah')->nullable();
            $table->unsignedBigInteger('siakad_idtawarmatakuliahdetail')->nullable();
            $table->unsignedBigInteger('id_unw_program_studi')->nullable();
            $table->string('edom_name_snapshot')->nullable();
            $table->text('study_program_snapshot')->nullable();
            $table->text('course_snapshot')->nullable();
            $table->string('lecturer_name_snapshot')->nullable();
            $table->string('lecturer_nidn_snapshot')->nullable();
            $table->string('respondent_name')->nullable();
            $table->string('student_number')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique([
                'edom_period_id',
                'edom_setting_id',
                'siakad_idmahasiswa',
                'siakad_idmatakuliah',
                'siakad_idtawarmatakuliahdetail',
            ], 'edom_response_student_section_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_response');
    }
};
