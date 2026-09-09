<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workflow_document_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->foreignId('opening_instance_stage_id')->constrained('workflow_instance_stages')->restrictOnDelete();
            $table->foreignId('requester_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('opened_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('parent_document_request_id')->nullable()->constrained('workflow_document_requests')->restrictOnDelete();
            $table->string('status', 30);
            $table->text('reason');
            $table->string('resolution', 30)->nullable();
            $table->text('resolution_comment')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['workflow_instance_id', 'status']);
        });

        DB::statement("CREATE UNIQUE INDEX workflow_document_requests_one_unresolved ON workflow_document_requests (workflow_instance_id) WHERE status IN ('awaiting_uploads', 'submitted', 'awaiting_re_review')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_document_requests');
    }
};
