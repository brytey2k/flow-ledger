<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('retirement_requests', function (Blueprint $table): void {
            $table->timestamp('settled_at')->nullable()->after('approved_at');
            $table->foreignId('settled_by_user_id')->nullable()->after('settled_at')->constrained('users')->nullOnDelete();
            $table->string('settlement_notes', 500)->nullable()->after('settled_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('retirement_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('settled_by_user_id');
            $table->dropColumn(['settled_at', 'settlement_notes']);
        });
    }
};
