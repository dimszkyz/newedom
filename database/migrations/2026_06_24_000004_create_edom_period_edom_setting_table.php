<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('edom_period_edom_setting')) {
            return;
        }

        Schema::create('edom_period_edom_setting', function (Blueprint $table) {
            $table->id();

            $table->foreignId('edom_period_id')
                ->constrained('edom_periods')
                ->cascadeOnDelete();

            $table->foreignId('edom_setting_id')
                ->constrained('edom_settings')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['edom_period_id', 'edom_setting_id'],
                'edom_period_setting_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_period_edom_setting');
    }
};
