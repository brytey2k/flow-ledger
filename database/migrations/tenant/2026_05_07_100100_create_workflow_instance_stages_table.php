<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_instance_stages', static function (Blueprint $table): void {
            $table->increments('id');
            $table->foreignId('workflow_instance_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->foreignId('workflow_stage_id')->constrained('workflow_stages')->restrictOnDelete();
            $table->string('status', 20)->default('pending'); // pending, active, approved, rejected, sent_back, skipped, cancelled
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['workflow_instance_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_instance_stages');
    }
};
