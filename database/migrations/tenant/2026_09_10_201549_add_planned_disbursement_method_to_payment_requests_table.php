<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_requests', static function (Blueprint $table): void {
            $table->string('planned_disbursement_method', 60)->nullable()->after('disbursed_by_user_id');
            $table->index(
                ['branch_id', 'status', 'planned_disbursement_method'],
                'payment_requests_branch_status_planned_method_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('payment_requests', static function (Blueprint $table): void {
            $table->dropIndex('payment_requests_branch_status_planned_method_index');
            $table->dropColumn('planned_disbursement_method');
        });
    }
};
