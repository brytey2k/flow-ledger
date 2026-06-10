<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('currency_denominations', function (Blueprint $table) {
            $table->string('type')->default('note')->after('label');

            $table->dropUnique(['currency_id', 'value']);
            $table->unique(['currency_id', 'value', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('currency_denominations', function (Blueprint $table) {
            $table->dropUnique(['currency_id', 'value', 'type']);
            $table->unique(['currency_id', 'value']);

            $table->dropColumn('type');
        });
    }
};
