<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edom_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('year');
            $table->unsignedBigInteger('siakad_idsemester');
            $table->timestamps();

            $table->unique(['year', 'siakad_idsemester'], 'edom_periods_year_semester_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_periods');
    }
};
