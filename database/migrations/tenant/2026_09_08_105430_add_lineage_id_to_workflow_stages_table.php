<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workflow_stages', static function (Blueprint $table): void {
            $table->uuid('lineage_id')->nullable()->after('workflow_template_id');
            $table->unique(['workflow_template_id', 'lineage_id']);
        });
    }

    public function down(): void
    {
        Schema::table('workflow_stages', static function (Blueprint $table): void {
            $table->dropUnique(['workflow_template_id', 'lineage_id']);
            $table->dropColumn('lineage_id');
        });
    }
};
