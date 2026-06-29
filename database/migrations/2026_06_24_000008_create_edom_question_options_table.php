<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('edom_question_options')) {
            return;
        }

        Schema::create('edom_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edom_setting_id')->nullable()->constrained('edom_settings')->cascadeOnDelete();
            $table->string('name');
            $table->integer('score');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_question_options');
    }
};
