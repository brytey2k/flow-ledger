<?php

declare(strict_types=1);

use App\Models\Tenant\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * MorphMapServiceProvider registered a morph alias for User ('user') after
 * model_has_roles/model_has_permissions rows had already been written with
 * the raw FQCN as model_type. Spatie/permission queries those pivot tables
 * by $model->getMorphClass(), which now resolves to the alias, so every
 * pre-existing row silently stopped matching and all users lost their
 * roles/permissions.
 */
return new class extends Migration {
    public function up(): void
    {
        foreach (['model_has_roles', 'model_has_permissions'] as $table) {
            DB::table($table)
                ->where('model_type', User::class)
                ->update(['model_type' => 'user']);
        }
    }

    public function down(): void
    {
        foreach (['model_has_roles', 'model_has_permissions'] as $table) {
            DB::table($table)
                ->where('model_type', 'user')
                ->update(['model_type' => User::class]);
        }
    }
};
