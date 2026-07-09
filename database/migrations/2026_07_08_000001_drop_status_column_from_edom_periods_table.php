<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edom_periods', function (Blueprint $table): void {
            if (Schema::hasColumn('edom_periods', 'status')) {
                $table->dropColumn('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('edom_periods', function (Blueprint $table): void {
            if (! Schema::hasColumn('edom_periods', 'status')) {
                $table->enum('status', ['draft', 'active', 'closed'])->default('active')->after('siakad_idsemester');
            }
        });
    }
};
