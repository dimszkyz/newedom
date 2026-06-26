<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edom_question_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('edom_setting_id')
                ->constrained('edom_settings')
                ->cascadeOnDelete();

            $table->string('name');
            $table->integer('score');
            $table->integer('sort_order')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_question_options');
    }
};
