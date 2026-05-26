<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workflow_instances', function (Blueprint $table) {
            $table->unsignedBigInteger('cancelled_at_stage_id')->nullable();
            $table->foreign('cancelled_at_stage_id')
                ->references('id')
                ->on('workflow_instance_stages')
                ->nullableOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_instances', function (Blueprint $table) {
            $table->dropForeignKey(['cancelled_at_stage_id']);
            $table->dropColumn('cancelled_at_stage_id');
        });
    }
};
