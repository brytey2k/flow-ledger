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
        Schema::create('workflow_review_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_document_request_id')->constrained('workflow_document_requests')->cascadeOnDelete();
            $table->foreignId('referring_instance_stage_id')->constrained('workflow_instance_stages')->restrictOnDelete();
            $table->foreignId('referred_workflow_action_id')->constrained('workflow_actions')->restrictOnDelete();
            $table->foreignId('referred_instance_stage_id')->constrained('workflow_instance_stages')->restrictOnDelete();
            $table->foreignId('referred_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewer_user_id')->constrained('users')->restrictOnDelete();
            $table->string('decision', 20)->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['workflow_document_request_id', 'referred_workflow_action_id']);
            $table->index(['reviewer_user_id', 'responded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_review_referrals');
    }
};
