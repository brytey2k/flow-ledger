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
        $exists = DB::select("SELECT 1 FROM information_schema.table_constraints WHERE table_name = 'staff' AND constraint_name = 'staff_user_id_unique'");

        if (empty($exists)) {
            Schema::table('staff', function (Blueprint $table) {
                $table->unique('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });
    }
};
