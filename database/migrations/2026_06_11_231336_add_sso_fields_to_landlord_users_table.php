<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('landlord_users', static function (Blueprint $table) {
            $table->string('oidc_sub')->nullable()->unique()->after('email');
            $table->boolean('is_oidc_user')->default(false)->after('oidc_sub');
        });
    }

    public function down(): void
    {
        Schema::table('landlord_users', static function (Blueprint $table) {
            $table->dropColumn(['oidc_sub', 'is_oidc_user']);
        });
    }
};
