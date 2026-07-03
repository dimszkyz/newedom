<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('edom_periods')
            && ! Schema::hasColumn('edom_periods', 'is_open_in_siakad')
        ) {
            Schema::table('edom_periods', function (Blueprint $table) {
                $table->boolean('is_open_in_siakad')
                    ->default(true)
                    ->after('siakad_idsemester');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('edom_periods')
            && Schema::hasColumn('edom_periods', 'is_open_in_siakad')
        ) {
            Schema::table('edom_periods', function (Blueprint $table) {
                $table->dropColumn('is_open_in_siakad');
            });
        }
    }
};
