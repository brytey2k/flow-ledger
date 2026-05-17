<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workflow_templates', static function (Blueprint $table): void {
            $table->unsignedBigInteger('branch_id')->nullable()->after('type');
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('workflow_templates', static function (Blueprint $table): void {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
