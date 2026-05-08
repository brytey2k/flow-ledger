<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_requests', static function (Blueprint $table): void {
            $table->increments('id');
            $table->foreignId('staff_id')->constrained('staff')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->string('type', 20); // advance, expense
            $table->string('status', 30)->default('draft');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->unsignedBigInteger('disbursed_by_user_id')->nullable();
            $table->string('disbursement_method', 60)->nullable();
            $table->string('disbursement_reference', 100)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['staff_id', 'status']);
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};
