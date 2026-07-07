<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('edom_period_edom_setting');
    }

    public function down(): void
    {
        // Pivot periode-setting tidak dibuat ulang karena periode EDOM tidak lagi memilih EDOM Settings.
    }
};
