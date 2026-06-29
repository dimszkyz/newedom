<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('edom_questions')) {
            return;
        }

        Schema::create('edom_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edom_setting_id')->constrained('edom_settings')->cascadeOnDelete();
            $table->foreignId('edom_question_category_id')->constrained('edom_question_categories')->cascadeOnDelete();
            $table->text('statement');
            $table->enum('question_type', ['option', 'text']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_questions');
    }
};
