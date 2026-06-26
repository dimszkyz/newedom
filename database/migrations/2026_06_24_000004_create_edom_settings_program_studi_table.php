<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edom_settings_program_studi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('edom_setting_id');
            $table->unsignedBigInteger('program_studi_id');
            $table->timestamps();

            $table->foreign('edom_setting_id', 'edom_study_programs_edom_id_foreign')
                ->references('id')
                ->on('edom_settings')
                ->cascadeOnDelete();

            $table->foreign('program_studi_id', 'edom_study_programs_study_program_id_foreign')
                ->references('id')
                ->on('program_studi')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_settings_program_studi');
    }
};
