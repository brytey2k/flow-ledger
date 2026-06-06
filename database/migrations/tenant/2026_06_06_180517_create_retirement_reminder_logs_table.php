<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('retirement_reminder_logs', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_request_id')->constrained('payment_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('notified_date');
            $table->timestamps();

            $table->unique(['payment_request_id', 'user_id', 'notified_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retirement_reminder_logs');
    }
};
