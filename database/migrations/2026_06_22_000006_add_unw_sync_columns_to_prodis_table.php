<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // No-op for English schema. UNW sync columns are created in study_programs.
    }

    public function down(): void
    {
        // No-op.
    }
};
