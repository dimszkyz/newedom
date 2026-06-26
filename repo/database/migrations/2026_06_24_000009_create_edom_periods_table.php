<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('edom_periods')) {
            return;
        }

        Schema::create('edom_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('year');
            $table->unsignedInteger('siakad_idsemester');
            $table->string('semester_name')->nullable();
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->unique(['year', 'siakad_idsemester'], 'edom_periods_year_semester_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_periods');
    }
};
