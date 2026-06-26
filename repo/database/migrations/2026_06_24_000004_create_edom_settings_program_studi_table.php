<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('edom_settings_program_studi')) {
            return;
        }

        Schema::create('edom_settings_program_studi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edom_setting_id')->constrained('edom_settings')->cascadeOnDelete();
            $table->foreignId('program_studi_id')->constrained('program_studi')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['edom_setting_id', 'program_studi_id'], 'edom_settings_program_studi_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_settings_program_studi');
    }
};
