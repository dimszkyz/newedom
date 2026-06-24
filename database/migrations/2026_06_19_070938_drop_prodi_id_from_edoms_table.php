<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edoms', function (Blueprint $table) {
            // Drop foreign key constraint terlebih dahulu sebelum drop kolom
            $table->dropForeign(['prodi_id']);
            $table->dropColumn('prodi_id');
        });
    }

    public function down(): void
    {
        Schema::table('edoms', function (Blueprint $table) {
            $table->foreignId('prodi_id')
                ->nullable()
                ->constrained('prodis')
                ->cascadeOnDelete();
        });
    }
};