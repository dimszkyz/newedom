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
            && Schema::hasColumn('edom_periods', 'status')
        ) {
            Schema::table('edom_periods', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('edom_periods')
            && ! Schema::hasColumn('edom_periods', 'status')
        ) {
            Schema::table('edom_periods', function (Blueprint $table) {
                $table->enum('status', ['draft', 'active', 'closed'])
                    ->default('draft')
                    ->after('siakad_idsemester');
            });
        }
    }
};
