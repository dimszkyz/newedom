<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // No-op: options now belong to edoms through edom_options.edom_id.
    }

    public function down(): void
    {
        // No-op.
    }
};
