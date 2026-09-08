<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_instance_stage_recovery_roles', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_instance_stage_id');
            $table->foreignId('role_id');
            $table->foreignId('applied_by_user_id')->nullable();
            $table->foreignId('prepared_workflow_template_id')->nullable();
            $table->text('reason');
            $table->string('template_repair_status', 20)->default('required');
            $table->timestamp('template_repair_prepared_at')->nullable();
            $table->timestamp('template_repair_published_at')->nullable();
            $table->timestamps();

            $table->foreign('workflow_instance_stage_id', 'wisrr_stage_fk')
                ->references('id')->on('workflow_instance_stages')->cascadeOnDelete();
            $table->foreign('role_id', 'wisrr_role_fk')
                ->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('applied_by_user_id', 'wisrr_user_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('prepared_workflow_template_id', 'wisrr_template_fk')
                ->references('id')->on('workflow_templates')->nullOnDelete();
            $table->unique(['workflow_instance_stage_id', 'role_id'], 'wisrr_stage_role_unique');
            $table->index('template_repair_status', 'wisrr_repair_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_instance_stage_recovery_roles');
    }
};
