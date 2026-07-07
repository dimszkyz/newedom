<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edom_response', function (Blueprint $table) {
            $table->id();

            $table->foreignId('edom_period_id')
                ->constrained('edom_periods')
                ->cascadeOnDelete();

            $table->foreignId('edom_setting_id')
                ->constrained('edom_settings')
                ->cascadeOnDelete();

            $table->string('siakad_idmahasiswa');
            $table->unsignedBigInteger('siakad_idmatakuliah');
            $table->unsignedBigInteger('siakad_idtawarmatakuliahdetail');
            $table->unsignedBigInteger('id_unw_program_studi')->nullable()->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(
                [
                    'edom_period_id',
                    'edom_setting_id',
                    'siakad_idmahasiswa',
                    'siakad_idmatakuliah',
                    'siakad_idtawarmatakuliahdetail',
                ],
                'edom_response_unique_student_section'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_response');
    }
};
