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
            $table->unsignedBigInteger('edom_setting_id')->nullable();
            $table->string('name');
            $table->integer('score');
            $table->integer('sort_order')->default(1);
            $table->timestamps();

            $table->foreign('edom_setting_id', 'edom_options_edom_id_foreign')
                ->references('id')
                ->on('edom_settings')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_question_options');
    }
};
