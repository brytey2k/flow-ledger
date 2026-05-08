<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('retirement_request_items', static function (Blueprint $table): void {
            $table->increments('id');
            $table->foreignId('retirement_request_id')->constrained('retirement_requests')->cascadeOnDelete();
            $table->foreignId('account_code_id')->constrained('account_codes')->restrictOnDelete();
            $table->string('description', 255);
            $table->decimal('amount', 15, 2);
            $table->string('receipt_number', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retirement_request_items');
    }
};
