<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edom_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('edom_id')->nullable();
            $table->string('edom_name_snapshot')->nullable();
            $table->text('study_program_snapshot')->nullable();
            $table->text('course_snapshot')->nullable();
            $table->string('respondent_name')->nullable();
            $table->string('student_number')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->foreign('edom_id')
                ->references('id')
                ->on('edom_settings')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_responses');
    }
};
