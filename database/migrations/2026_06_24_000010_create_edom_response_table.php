<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('edom_response')) {
            return;
        }

        Schema::create('edom_response', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edom_period_id')->nullable()->constrained('edom_periods')->nullOnDelete();
            $table->foreignId('edom_setting_id')->nullable()->constrained('edom_settings')->nullOnDelete();
            $table->string('siakad_idmahasiswa')->nullable();
            $table->unsignedBigInteger('siakad_idmatakuliah')->nullable();
            $table->unsignedBigInteger('siakad_idtawarmatakuliahdetail')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_response');
    }
};
