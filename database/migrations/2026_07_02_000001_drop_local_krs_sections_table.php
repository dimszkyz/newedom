<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('local_krs_sections');
    }

    public function down(): void
    {
        // Data KRS percobaan tidak dibuat kembali.
    }
};
