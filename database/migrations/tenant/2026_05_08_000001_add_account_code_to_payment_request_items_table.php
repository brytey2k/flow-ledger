<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_request_items', static function (Blueprint $table): void {
            $table->foreignId('account_code_id')->nullable()->after('amount')->constrained('account_codes')->nullOnDelete();
            $table->string('receipt_number', 100)->nullable()->after('account_code_id');
        });
    }

    public function down(): void
    {
        Schema::table('payment_request_items', static function (Blueprint $table): void {
            $table->dropForeign(['account_code_id']);
            $table->dropColumn(['account_code_id', 'receipt_number']);
        });
    }
};
