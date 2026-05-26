<?php

declare(strict_types=1);

use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\WorkflowInstance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workflow_instances', static function (Blueprint $table): void {
            // No FK constraints — source models use SoftDeletes; consistent with sent_back_to_stage_id pattern
            $table->unsignedBigInteger('branch_id')->nullable()->after('submitter_user_id');
            $table->unsignedBigInteger('department_id')->nullable()->after('branch_id');
            $table->index('branch_id');
            $table->index('department_id');
        });

        Schema::table('workflow_instance_stages', static function (Blueprint $table): void {
            $table->index('workflow_stage_id');
        });

        // Backfill existing records
        WorkflowInstance::with(['workflowable', 'submitter.staffProfile'])
            ->chunkById(200, static function ($instances): void {
                foreach ($instances as $instance) {
                    $branchId = $instance->workflowable?->getAttribute('branch_id');

                    if ($branchId === null && $instance->workflowable instanceof RetirementRequest) {
                        $branchId = $instance->workflowable->paymentRequest?->branch_id;
                    }

                    $instance->updateQuietly([
                        'branch_id' => $branchId,
                        'department_id' => $instance->submitter?->staffProfile?->department_id,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('workflow_instances', static function (Blueprint $table): void {
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['department_id']);
            $table->dropColumn(['branch_id', 'department_id']);
        });

        Schema::table('workflow_instance_stages', static function (Blueprint $table): void {
            $table->dropIndex(['workflow_stage_id']);
        });
    }
};
