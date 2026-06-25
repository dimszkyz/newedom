<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('edom_responses')) {
            return;
        }

        Schema::create('edom_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edom_id')
                ->nullable()
                ->constrained('edom_settings')
                ->nullOnDelete();
            $table->string('edom_name_snapshot')->nullable();
            $table->text('study_program_snapshot')->nullable();
            $table->text('course_snapshot')->nullable();
            $table->string('respondent_name')->nullable();
            $table->string('student_number')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_responses');
    }
};
