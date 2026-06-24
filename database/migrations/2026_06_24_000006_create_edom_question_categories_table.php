<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edom_question_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('edom_setting_id');
            $table->string('name');
            $table->timestamps();

            $table->foreign('edom_setting_id', 'edom_categories_edom_id_foreign')
                ->references('id')
                ->on('edom_settings')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_question_categories');
    }
};
