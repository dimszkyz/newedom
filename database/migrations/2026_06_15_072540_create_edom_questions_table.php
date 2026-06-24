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

            $table->foreignId('category_id')
                ->constrained('edom_categories')
                ->cascadeOnDelete();

            $table->text('statement');

            $table->enum('question_type', [
                'multiple_choice',
                'essay',
            ]);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_questions');
    }
};
