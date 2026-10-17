<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('landlord_audit_logs', static function (Blueprint $table): void {
            $table->string('artifact_path')->nullable()->after('error');
        });
    }

    public function down(): void
    {
        Schema::table('landlord_audit_logs', static function (Blueprint $table): void {
            $table->dropColumn('artifact_path');
        });
    }
};
