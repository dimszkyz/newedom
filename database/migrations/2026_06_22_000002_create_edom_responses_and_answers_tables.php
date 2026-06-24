<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('edom_responses')) {
            Schema::create('edom_responses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('edom_id')->nullable()->constrained('edoms')->nullOnDelete();
                $table->string('edom_name_snapshot')->nullable();
                $table->text('study_program_snapshot')->nullable();
                $table->text('course_snapshot')->nullable();
                $table->string('respondent_name')->nullable();
                $table->string('student_number')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('edom_answers')) {
            Schema::create('edom_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('edom_response_id')->constrained('edom_responses')->cascadeOnDelete();
                $table->foreignId('edom_question_id')->nullable()->constrained('edom_questions')->nullOnDelete();
                $table->string('category_name_snapshot')->nullable();
                $table->text('statement_snapshot')->nullable();
                $table->foreignId('edom_option_id')->nullable()->constrained('edom_options')->nullOnDelete();
                $table->string('option_label_snapshot')->nullable();
                $table->integer('option_score_snapshot')->nullable();
                $table->text('answer_text')->nullable();
                $table->integer('score')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Manual rollback if needed: drop edom_answers then edom_responses.
    }
};
