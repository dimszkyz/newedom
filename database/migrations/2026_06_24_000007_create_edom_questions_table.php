<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edom_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('edom_question_category_id');
            $table->text('statement');
            $table->enum('question_type', ['multiple_choice', 'essay']);
            $table->timestamps();

            $table->foreign('edom_question_category_id', 'edom_questions_category_id_foreign')
                ->references('id')
                ->on('edom_question_categories')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_questions');
    }
};
