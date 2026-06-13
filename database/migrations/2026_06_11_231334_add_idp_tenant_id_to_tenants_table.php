<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', static function (Blueprint $table) {
            $table->string('idp_tenant_id')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', static function (Blueprint $table) {
            $table->dropColumn('idp_tenant_id');
        });
    }
};
