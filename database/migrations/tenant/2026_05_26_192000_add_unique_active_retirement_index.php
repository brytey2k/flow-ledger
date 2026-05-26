<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        // Create a partial unique index to ensure only one active (non-cancelled) retirement per payment_request
        // This uses PostgreSQL partial index syntax
        Illuminate\Support\Facades\DB::statement(
            "CREATE UNIQUE INDEX IF NOT EXISTS uniq_active_retirement_per_payment_request ON retirement_requests (payment_request_id) WHERE status != 'cancelled'",
        );
    }

    public function down(): void
    {
        Illuminate\Support\Facades\DB::statement(
            'DROP INDEX IF EXISTS uniq_active_retirement_per_payment_request',
        );
    }
};
