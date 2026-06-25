<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('edom_courses')) {
            return;
        }

        Schema::create('edom_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edom_id')
                ->constrained('edom_settings')
                ->cascadeOnDelete();
            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnDelete();
            $table->unique(['edom_id', 'course_id'], 'edom_courses_edom_id_course_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_courses');
    }
};
