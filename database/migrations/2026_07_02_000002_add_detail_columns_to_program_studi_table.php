<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('program_studi')) {
            return;
        }

        Schema::table('program_studi', function (Blueprint $table) {
            if (! Schema::hasColumn('program_studi', 'slug')) {
                $table->string('slug')->nullable()->after('nama');
            }

            if (! Schema::hasColumn('program_studi', 'page_slug')) {
                $table->string('page_slug')->nullable()->after('slug');
            }

            if (! Schema::hasColumn('program_studi', 'jenjang')) {
                $table->string('jenjang')->nullable()->after('page_slug');
            }

            if (! Schema::hasColumn('program_studi', 'jenjang_nama_singkat')) {
                $table->string('jenjang_nama_singkat')->nullable()->after('jenjang');
            }

            if (! Schema::hasColumn('program_studi', 'id_unw_fakultas')) {
                $table->unsignedBigInteger('id_unw_fakultas')->nullable()->after('jenjang_nama_singkat');
            }

            if (! Schema::hasColumn('program_studi', 'nama_fakultas')) {
                $table->string('nama_fakultas')->nullable()->after('id_unw_fakultas');
            }

            if (! Schema::hasColumn('program_studi', 'page_slug_fakultas')) {
                $table->string('page_slug_fakultas')->nullable()->after('nama_fakultas');
            }

            if (! Schema::hasColumn('program_studi', 'api_updated_at')) {
                $table->timestamp('api_updated_at')->nullable()->after('page_slug_fakultas');
            }

            if (! Schema::hasColumn('program_studi', 'synced_at')) {
                $table->timestamp('synced_at')->nullable()->after('api_updated_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('program_studi')) {
            return;
        }

        Schema::table('program_studi', function (Blueprint $table) {
            foreach ([
                'synced_at',
                'api_updated_at',
                'page_slug_fakultas',
                'nama_fakultas',
                'id_unw_fakultas',
                'jenjang_nama_singkat',
                'jenjang',
                'page_slug',
                'slug',
            ] as $column) {
                if (Schema::hasColumn('program_studi', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
