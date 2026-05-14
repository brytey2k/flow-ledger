<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workflow_instances', static function (Blueprint $table): void {
            $table->foreignId('submitter_user_id')->nullable()->after('status')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('workflow_instances', static function (Blueprint $table): void {
            $table->dropForeignIdFor(App\Models\Tenant\User::class, 'submitter_user_id');
            $table->dropColumn('submitter_user_id');
        });
    }
};
