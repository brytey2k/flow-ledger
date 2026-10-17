<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_health_snapshots', static function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->unique();
            $table->json('snapshot')->nullable();
            $table->json('attention');
            $table->boolean('needs_attention')->default(false)->index();
            $table->text('error')->nullable();
            $table->timestamp('captured_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_health_snapshots');
    }
};
