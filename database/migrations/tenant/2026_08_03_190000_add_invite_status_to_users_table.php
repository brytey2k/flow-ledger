<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('active')->after('is_oidc_user');
            $table->timestamp('invited_at')->nullable()->after('status');
            $table->unsignedBigInteger('invited_by')->nullable()->after('invited_at');
            $table->timestamp('activated_at')->nullable()->after('invited_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status', 'invited_at', 'invited_by', 'activated_at']);
        });
    }
};
