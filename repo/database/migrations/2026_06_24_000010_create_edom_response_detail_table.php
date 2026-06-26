<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edom_response_detail', function (Blueprint $table) {
            $table->id();

            $table->foreignId('edom_response_id')
                ->constrained('edom_response')
                ->cascadeOnDelete();

            $table->foreignId('edom_question_id')
                ->constrained('edom_questions')
                ->cascadeOnDelete();

            $table->foreignId('edom_option_id')
                ->nullable()
                ->constrained('edom_question_options')
                ->nullOnDelete();

            $table->text('answer_text')->nullable();
            $table->timestamps();

            $table->unique(['edom_response_id', 'edom_question_id'], 'edom_response_detail_unique_question');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_response_detail');
    }
};
