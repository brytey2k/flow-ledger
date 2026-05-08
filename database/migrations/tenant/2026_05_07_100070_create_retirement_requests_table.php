<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('retirement_requests', static function (Blueprint $table): void {
            $table->increments('id');
            $table->foreignId('payment_request_id')->constrained('payment_requests')->restrictOnDelete();
            $table->string('status', 30)->default('draft');
            $table->decimal('total_amount_expended', 15, 2)->default(0);
            $table->decimal('difference_amount', 15, 2)->default(0);
            $table->string('difference_type', 30)->nullable(); // refund_to_company, pay_to_staff, nil
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('payment_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retirement_requests');
    }
};
