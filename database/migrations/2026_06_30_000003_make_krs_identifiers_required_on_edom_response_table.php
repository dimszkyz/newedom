<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('edom_response')) {
            return;
        }

        $hasIncompleteResponses = DB::table('edom_response')
            ->whereNull('siakad_idmatakuliah')
            ->orWhereNull('siakad_idtawarmatakuliahdetail')
            ->exists();

        if ($hasIncompleteResponses) {
            throw new RuntimeException(
                'Kolom KRS pada edom_response masih memiliki nilai null. '
                .'Perbaiki atau hapus respons legacy tersebut sebelum menjalankan migrasi ini.'
            );
        }

        Schema::table('edom_response', function (Blueprint $table) {
            $table->unsignedBigInteger('siakad_idmatakuliah')->nullable(false)->change();
            $table->unsignedBigInteger('siakad_idtawarmatakuliahdetail')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('edom_response')) {
            return;
        }

        Schema::table('edom_response', function (Blueprint $table) {
            $table->unsignedBigInteger('siakad_idmatakuliah')->nullable()->change();
            $table->unsignedBigInteger('siakad_idtawarmatakuliahdetail')->nullable()->change();
        });
    }
};
