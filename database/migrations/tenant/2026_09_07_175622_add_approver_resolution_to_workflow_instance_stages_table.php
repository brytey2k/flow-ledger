<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workflow_instance_stages', static function (Blueprint $table): void {
            $table->string('approver_pool', 20)->nullable()->default('primary')->after('status');
            $table->string('blocked_reason', 100)->nullable()->after('approver_pool');
            $table->timestamp('blocked_at')->nullable()->after('blocked_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_instance_stages', static function (Blueprint $table): void {
            $table->dropColumn(['approver_pool', 'blocked_reason', 'blocked_at']);
        });
    }
};
