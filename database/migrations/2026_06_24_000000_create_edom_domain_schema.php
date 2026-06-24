<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_studi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_unw_program_studi')->nullable()->unique('study_programs_unw_study_program_id_unique');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('page_slug')->nullable();
            $table->string('degree_level')->nullable();
            $table->string('degree_short_name')->nullable();
            $table->unsignedBigInteger('unw_faculty_id')->nullable();
            $table->string('faculty_name')->nullable();
            $table->string('faculty_page_slug')->nullable();
            $table->timestamp('api_updated_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('study_program_id');
            $table->string('name');
            $table->timestamps();

            $table->foreign('study_program_id')
                ->references('id')
                ->on('program_studi')
                ->cascadeOnDelete();
        });

        Schema::create('edom_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('created_date');
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->timestamps();
        });

        Schema::create('edom_settings_program_studi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('edom_setting_id');
            $table->unsignedBigInteger('program_studi_id');
            $table->timestamps();

            $table->foreign('edom_setting_id')
                ->references('id')
                ->on('edom_settings')
                ->cascadeOnDelete();

            $table->foreign('program_studi_id')
                ->references('id')
                ->on('program_studi')
                ->cascadeOnDelete();
        });

        Schema::create('edom_courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('edom_id');
            $table->unsignedBigInteger('course_id');

            $table->unique(['edom_id', 'course_id'], 'edom_courses_edom_id_course_id_unique');

            $table->foreign('edom_id')
                ->references('id')
                ->on('edom_settings')
                ->cascadeOnDelete();

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->cascadeOnDelete();
        });

        Schema::create('edom_question_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('edom_setting_id');
            $table->string('name');
            $table->timestamps();

            $table->foreign('edom_setting_id')
                ->references('id')
                ->on('edom_settings')
                ->cascadeOnDelete();
        });

        Schema::create('edom_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('edom_question_category_id');
            $table->text('statement');
            $table->enum('question_type', ['multiple_choice', 'essay']);
            $table->timestamps();

            $table->foreign('edom_question_category_id')
                ->references('id')
                ->on('edom_question_categories')
                ->cascadeOnDelete();
        });

        Schema::create('edom_question_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('edom_setting_id')->nullable();
            $table->string('name');
            $table->integer('score');
            $table->integer('sort_order')->default(1);
            $table->timestamps();

            $table->foreign('edom_setting_id')
                ->references('id')
                ->on('edom_settings')
                ->cascadeOnDelete();
        });

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

        Schema::create('edom_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('edom_response_id');
            $table->unsignedBigInteger('edom_question_id')->nullable();
            $table->string('category_name_snapshot')->nullable();
            $table->text('statement_snapshot')->nullable();
            $table->unsignedBigInteger('edom_option_id')->nullable();
            $table->string('option_label_snapshot')->nullable();
            $table->integer('option_score_snapshot')->nullable();
            $table->text('answer_text')->nullable();
            $table->integer('score')->nullable();
            $table->timestamps();

            $table->foreign('edom_response_id')
                ->references('id')
                ->on('edom_responses')
                ->cascadeOnDelete();

            $table->foreign('edom_question_id')
                ->references('id')
                ->on('edom_questions')
                ->nullOnDelete();

            $table->foreign('edom_option_id')
                ->references('id')
                ->on('edom_question_options')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_answers');
        Schema::dropIfExists('edom_responses');
        Schema::dropIfExists('edom_question_options');
        Schema::dropIfExists('edom_questions');
        Schema::dropIfExists('edom_question_categories');
        Schema::dropIfExists('edom_courses');
        Schema::dropIfExists('edom_settings_program_studi');
        Schema::dropIfExists('edom_settings');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('program_studi');
    }
};
