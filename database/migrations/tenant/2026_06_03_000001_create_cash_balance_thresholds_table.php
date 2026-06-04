<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_balance_thresholds', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->unique()->constrained('branches')->cascadeOnDelete();
            $table->decimal('threshold_amount', 15, 2);
            $table->json('notification_user_ids')->nullable(); // JSON array of user IDs to notify
            $table->integer('cooldown_minutes')->default(1440); // Default: 24 hours (1440 minutes)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_balance_thresholds');
    }
};
