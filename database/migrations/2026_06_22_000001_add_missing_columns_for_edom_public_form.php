<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('edoms') && ! Schema::hasColumn('edoms', 'status')) {
            Schema::table('edoms', function (Blueprint $table) {
                $table->string('status')->default('draft')->after('created_date');
            });
        }

        if (Schema::hasTable('edom_options') && ! Schema::hasColumn('edom_options', 'edom_id')) {
            Schema::table('edom_options', function (Blueprint $table) {
                $table->foreignId('edom_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('edoms')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Manual rollback if needed.
    }
};
