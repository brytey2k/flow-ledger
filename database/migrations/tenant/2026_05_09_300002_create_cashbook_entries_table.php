<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cashbook_entries', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cashbook_id')->constrained('cashbooks')->cascadeOnDelete();
            $table->string('type', 10); // 'debit' or 'credit'
            $table->decimal('amount', 15, 2);
            $table->string('description', 255);
            $table->date('entry_date');
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->nullableMorphs('sourceable'); // sourceable_type, sourceable_id
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['cashbook_id', 'type']);
            $table->index(['cashbook_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashbook_entries');
    }
};
