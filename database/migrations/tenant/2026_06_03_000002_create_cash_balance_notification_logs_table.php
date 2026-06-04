<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_balance_notification_logs', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cash_balance_threshold_id')->constrained('cash_balance_thresholds')->cascadeOnDelete();
            $table->decimal('balance_amount', 15, 2);
            $table->timestamp('notified_at')->useCurrent();

            $table->index(['cash_balance_threshold_id', 'notified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_balance_notification_logs');
    }
};
