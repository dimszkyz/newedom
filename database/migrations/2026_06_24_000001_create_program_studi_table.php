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
            $table->string('slug')->nullable();
            $table->string('page_slug')->nullable();
            $table->string('jenjang')->nullable();
            $table->string('jenjang_nama_singkat')->nullable();
            $table->unsignedBigInteger('unw_fakultas_id')->nullable();
            $table->string('unw_fakultas_nama')->nullable();
            $table->string('unw_fakultas_page_slug')->nullable();
            $table->timestamp('api_created_at')->nullable();
            $table->timestamp('api_updated_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_studi');
    }
};
