<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_instances', static function (Blueprint $table): void {
            $table->increments('id');
            $table->foreignId('workflow_template_id')->constrained('workflow_templates')->restrictOnDelete();
            $table->morphs('workflowable');
            $table->string('status', 20)->default('in_progress'); // in_progress, completed, cancelled
            // Not a FK to avoid circular dependency with workflow_instance_stages
            $table->unsignedBigInteger('sent_back_to_stage_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_instances');
    }
};
