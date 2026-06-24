<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // No-op for English schema. Snapshot columns are created in the main response/answer migration.
    }

    public function down(): void
    {
        // No-op.
    }
};
