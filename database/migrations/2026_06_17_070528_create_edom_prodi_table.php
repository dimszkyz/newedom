<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edom_study_programs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('edom_id')
                ->constrained('edoms')
                ->cascadeOnDelete();

            $table->foreignId('study_program_id')
                ->constrained('study_programs')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'edom_id',
                'study_program_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_study_programs');
    }
};
