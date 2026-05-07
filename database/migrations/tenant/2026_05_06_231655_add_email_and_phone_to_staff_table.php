<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('staff', static function (Blueprint $table): void {
            $table->string('email', 150)->nullable()->unique()->after('last_name');
            $table->string('phone', 30)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('staff', static function (Blueprint $table): void {
            $table->dropColumn(['email', 'phone']);
        });
    }
};
