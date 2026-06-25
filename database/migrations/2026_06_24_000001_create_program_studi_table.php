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
            $table->unsignedBigInteger('id_unw_program_studi')->nullable()->unique('study_programs_unw_study_program_id_unique');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('page_slug')->nullable();
            $table->string('degree_level')->nullable();
            $table->string('degree_short_name')->nullable();
            $table->unsignedBigInteger('unw_faculty_id')->nullable();
            $table->string('faculty_name')->nullable();
            $table->string('faculty_page_slug')->nullable();
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
