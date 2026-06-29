<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('edom_periods', 'siakad_idtahunajaran') && ! Schema::hasColumn('edom_periods', 'year')) {
            Schema::table('edom_periods', function (Blueprint $table) {
                $table->renameColumn('siakad_idtahunajaran', 'year');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('edom_periods', 'year') && ! Schema::hasColumn('edom_periods', 'siakad_idtahunajaran')) {
            Schema::table('edom_periods', function (Blueprint $table) {
                $table->renameColumn('year', 'siakad_idtahunajaran');
            });
        }
    }
};
