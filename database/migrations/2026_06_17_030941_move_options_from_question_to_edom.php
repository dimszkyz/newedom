<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // No-op: the English schema already stores options at EDOM level.
    }

    public function down(): void
    {
        // No-op.
    }
};
