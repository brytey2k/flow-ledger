<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_counts', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cashbook_id')->constrained('cashbooks')->restrictOnDelete();
            $table->foreignId('counted_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('counted_at');
            $table->decimal('cashbook_balance_at_count', 15, 2);
            $table->decimal('counted_total', 15, 2);
            $table->decimal('difference', 15, 2);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['cashbook_id', 'counted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_counts');
    }
};
