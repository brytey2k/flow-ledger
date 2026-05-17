<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::rename('account_codes', 'cost_codes');

        Schema::table('payment_request_items', function (Blueprint $table): void {
            $table->renameColumn('account_code_id', 'cost_code_id');
        });

        Schema::table('retirement_request_items', function (Blueprint $table): void {
            $table->renameColumn('account_code_id', 'cost_code_id');
        });
    }

    public function down(): void
    {
        Schema::rename('cost_codes', 'account_codes');

        Schema::table('payment_request_items', function (Blueprint $table): void {
            $table->renameColumn('cost_code_id', 'account_code_id');
        });

        Schema::table('retirement_request_items', function (Blueprint $table): void {
            $table->renameColumn('cost_code_id', 'account_code_id');
        });
    }
};
