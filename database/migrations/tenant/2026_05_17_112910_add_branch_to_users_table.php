<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add columns as nullable first so we can backfill existing rows
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('branch_id')->nullable()->after('locale');
            $table->unsignedBigInteger('operational_branch_id')->nullable()->after('branch_id');
        });

        // Ensure a default level and branch exist so every user can be assigned one
        $levelId = DB::table('levels')->orderBy('id')->value('id');
        if ($levelId === null) {
            $levelId = DB::table('levels')->insertGetId([
                'name' => 'Default',
                'position' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $branchId = DB::table('branches')->orderBy('id')->value('id');
        if ($branchId === null) {
            $branchId = DB::table('branches')->insertGetId([
                'name' => 'Head Office',
                'level_id' => $levelId,
                'position' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Seed the closure table self-referential entry required by ClosureTable
            DB::table('branches_tree')->insert([
                'ancestor_id' => $branchId,
                'descendant_id' => $branchId,
                'depth' => 0,
            ]);
        }

        // Backfill all existing users with the default (or first) branch
        DB::table('users')->whereNull('branch_id')->update([
            'branch_id' => $branchId,
            'operational_branch_id' => $branchId,
        ]);

        // Now make columns non-nullable and add FK constraints
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('branch_id')->nullable(false)->change();
            $table->unsignedBigInteger('operational_branch_id')->nullable(false)->change();
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
            $table->foreign('operational_branch_id')->references('id')->on('branches')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['operational_branch_id']);
            $table->dropColumn(['branch_id', 'operational_branch_id']);
        });
    }
};
