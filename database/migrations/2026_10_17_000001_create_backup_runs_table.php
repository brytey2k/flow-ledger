<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('backup_runs', static function (Blueprint $table): void {
            $table->id();
            $table->string('database_name');
            $table->string('tenant_id')->nullable()->index();
            $table->string('status', 20)->default('running');
            $table->string('path')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->text('error')->nullable();
            $table->boolean('is_monthly')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('verify_status', 20)->nullable();
            $table->text('verify_error')->nullable();
            $table->timestamp('pruned_at')->nullable();
            $table->timestamps();

            $table->index(['database_name', 'status', 'finished_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_runs');
    }
};
