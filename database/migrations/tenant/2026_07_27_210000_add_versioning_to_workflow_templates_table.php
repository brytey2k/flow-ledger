<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workflow_templates', static function (Blueprint $table): void {
            $table->uuid('template_group_id')->nullable()->after('id');
            $table->unsignedInteger('version')->default(1)->after('branch_id');
            $table->boolean('is_current')->default(true)->after('version');
        });

        DB::table('workflow_templates')->select('id')->orderBy('id')->each(static function (object $row): void {
            DB::table('workflow_templates')
                ->where('id', $row->id)
                ->update(['template_group_id' => (string) Str::uuid()]);
        });

        Schema::table('workflow_templates', static function (Blueprint $table): void {
            $table->uuid('template_group_id')->nullable(false)->change();

            $table->index(['template_group_id', 'is_current']);
            $table->index(['template_group_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::table('workflow_templates', static function (Blueprint $table): void {
            $table->dropIndex(['template_group_id', 'is_current']);
            $table->dropIndex(['template_group_id', 'version']);
            $table->dropColumn(['template_group_id', 'version', 'is_current']);
        });
    }
};
