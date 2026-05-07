<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('users', 'name') && ! Schema::hasColumn('users', 'first_name')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('first_name')->after('id')->default('');
                $table->string('last_name')->after('first_name')->default('');
            });

            DB::table('users')->update([
                'first_name' => DB::raw("split_part(name, ' ', 1)"),
                'last_name' => DB::raw("TRIM(SUBSTRING(name FROM POSITION(' ' IN name)))"),
            ]);

            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'first_name') && ! Schema::hasColumn('users', 'name')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('name')->after('id')->default('');
            });

            DB::table('users')->update([
                'name' => DB::raw("CONCAT(first_name, ' ', last_name)"),
            ]);

            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn(['first_name', 'last_name']);
            });
        }
    }
};
