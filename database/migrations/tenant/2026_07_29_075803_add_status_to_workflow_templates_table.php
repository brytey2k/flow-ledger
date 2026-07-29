<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workflow_templates', static function (Blueprint $table): void {
            $table->string('status', 20)->default('published')->after('is_current');

            $table->index(['template_group_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('workflow_templates', static function (Blueprint $table): void {
            $table->dropIndex(['template_group_id', 'status']);
            $table->dropColumn('status');
        });
    }
};
