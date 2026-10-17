<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $duplicates = DB::table('cashbook_entries')
            ->whereNotNull('sourceable_id')
            ->whereNull('deleted_at')
            ->select('sourceable_type', 'sourceable_id', 'type')
            ->groupBy('sourceable_type', 'sourceable_id', 'type')
            ->havingRaw('count(*) > 1')
            ->count();

        if ($duplicates > 0) {
            throw new RuntimeException("{$duplicates} request(s) already have more than one cashbook entry of the same type. Review and remove the duplicates, then run this migration again.");
        }

        // A disbursement or settlement posts at most one entry of each type per request, so a second concurrent
        // attempt is rejected by the database even if the application's own lock and status check were bypassed.
        DB::statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS cashbook_entries_one_per_source ON cashbook_entries (sourceable_type, sourceable_id, type) WHERE sourceable_id IS NOT NULL AND deleted_at IS NULL',
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS cashbook_entries_one_per_source');
    }
};
