<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('landlord_audit_logs', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('landlord_user_id')->nullable();
            $table->string('landlord_user_email')->nullable();
            $table->string('tenant_id')->index();
            $table->string('action', 60);
            $table->string('status', 20)->default('queued');
            $table->json('filters')->nullable();
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landlord_audit_logs');
    }
};
