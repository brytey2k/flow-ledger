<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_stages', static function (Blueprint $table): void {
            $table->increments('id');
            $table->foreignId('workflow_template_id')->constrained('workflow_templates')->cascadeOnDelete();
            $table->foreignId('parallel_group_id')->nullable()->constrained('workflow_parallel_groups')->nullOnDelete();
            $table->string('name', 150);
            $table->unsignedSmallInteger('display_order')->default(1);
            $table->decimal('skip_below_amount', 15, 2)->nullable();
            $table->timestamps();

            $table->index(['workflow_template_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_stages');
    }
};
