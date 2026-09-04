<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->after('domain');
        });

        // Every existing tenant has exactly one domain today; mark it primary
        // so RootUrlTenancyBootstrapper's replacement (tenant_current_domain())
        // has a deterministic fallback for tenants with no HTTP request context.
        DB::table('domains')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MIN(id)')->from('domains')->groupBy('tenant_id');
            })
            ->update(['is_primary' => true]);
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });
    }
};
