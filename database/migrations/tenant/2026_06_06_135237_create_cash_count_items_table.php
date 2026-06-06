<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_count_items', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cash_count_id')->constrained('cash_counts')->cascadeOnDelete();
            $table->foreignId('denomination_id')->constrained('currency_denominations')->restrictOnDelete();
            $table->decimal('denomination_value', 15, 4);
            $table->string('denomination_label', 100);
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->unique(['cash_count_id', 'denomination_id']);
            $table->index('cash_count_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_count_items');
    }
};
