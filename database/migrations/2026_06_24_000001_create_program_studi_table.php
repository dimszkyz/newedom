<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('program_studi')) {
            return;
        }

        Schema::create('program_studi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_unw_program_studi')->nullable()->unique();
            $table->string('nama');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_studi');
    }
};
