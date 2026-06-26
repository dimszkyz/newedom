<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edom_courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('edom_id');
            $table->unsignedBigInteger('course_id');

            $table->unique(['edom_id', 'course_id'], 'edom_courses_edom_id_course_id_unique');

            $table->foreign('edom_id')
                ->references('id')
                ->on('edom_settings')
                ->cascadeOnDelete();

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_courses');
    }
};
