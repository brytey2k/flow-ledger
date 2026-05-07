<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('branches', static function (Blueprint $table): void {
            $table->foreignId('currency_id')->nullable()->after('level_id')->constrained()->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::table('branches', static function (Blueprint $table): void {
            $table->dropForeign(['currency_id']);
            $table->dropColumn('currency_id');
        });
    }
};
