<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('edom_response_detail')) {
            return;
        }

        Schema::create('edom_response_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edom_response_id')->constrained('edom_response')->cascadeOnDelete();
            $table->foreignId('edom_question_id')->nullable()->constrained('edom_questions')->nullOnDelete();
            $table->foreignId('edom_question_option_id')->nullable()->constrained('edom_question_options')->nullOnDelete();
            $table->string('category_name_snapshot')->nullable();
            $table->text('statement_snapshot')->nullable();
            $table->string('option_label_snapshot')->nullable();
            $table->integer('option_score_snapshot')->nullable();
            $table->text('answer_text')->nullable();
            $table->integer('score')->nullable();
            $table->timestamps();

            $table->unique(['edom_response_id', 'edom_question_id'], 'edom_response_detail_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_response_detail');
    }
};
