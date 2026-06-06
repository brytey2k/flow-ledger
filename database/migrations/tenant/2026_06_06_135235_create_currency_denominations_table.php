<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('currency_denominations', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('value', 15, 4);
            $table->string('label', 100);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['currency_id', 'value']);
            $table->index(['currency_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_denominations');
    }
};
