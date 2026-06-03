<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('retirement_requests', function (Blueprint $table): void {
            $table->boolean('no_money_spent')->default(false)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('retirement_requests', function (Blueprint $table): void {
            $table->dropColumn('no_money_spent');
        });
    }
};
