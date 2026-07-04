<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('edom_periods')) {
            $hasUpdatePermission = Schema::hasColumn('edom_periods', 'allows_response_updates');

            Schema::table('edom_periods', function (Blueprint $table) use ($hasUpdatePermission) {
                $table->boolean('is_open_in_siakad')
                    ->default(false)
                    ->change();

                if (! $hasUpdatePermission) {
                    $table->boolean('allows_response_updates')
                        ->default(false)
                        ->after('is_open_in_siakad');
                }
            });

            DB::table('edom_periods')
                ->where('is_open_in_siakad', true)
                ->update(['allows_response_updates' => true]);
        }

        if (! Schema::hasTable('edom_period_edom_setting')) {
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
                    'edom_period_setting_unique',
                );
            });
        }

        if (
            Schema::hasTable('edom_period_edom_setting')
            && Schema::hasTable('edom_periods')
            && Schema::hasTable('edom_settings')
        ) {
            $periodIds = DB::table('edom_periods')->pluck('id');
            $settingIds = DB::table('edom_settings')->pluck('id');
            $now = now();

            foreach ($periodIds as $periodId) {
                foreach ($settingIds as $settingId) {
                    DB::table('edom_period_edom_setting')->insertOrIgnore([
                        'edom_period_id' => $periodId,
                        'edom_setting_id' => $settingId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('edom_period_edom_setting');

        if (Schema::hasTable('edom_periods')) {
            $hasUpdatePermission = Schema::hasColumn('edom_periods', 'allows_response_updates');

            Schema::table('edom_periods', function (Blueprint $table) use ($hasUpdatePermission) {
                if ($hasUpdatePermission) {
                    $table->dropColumn('allows_response_updates');
                }

                $table->boolean('is_open_in_siakad')
                    ->default(true)
                    ->change();
            });
        }
    }
};
