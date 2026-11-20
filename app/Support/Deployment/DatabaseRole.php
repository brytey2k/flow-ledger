<?php

declare(strict_types=1);

namespace App\Support\Deployment;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The Postgres role the app connects as. One role serves the central database and every tenant database: it creates
 * each new organisation's database (so it needs CREATEDB), owns it, and reaches it at runtime and for backups.
 */
class DatabaseRole
{
    /** @return array{name: string, superuser: bool, createdb: bool}|null Null when the central database cannot be reached */
    public function privileges(): array|null
    {
        try {
            $role = DB::connection(config()->string('tenancy.database.central_connection'))
                ->selectOne('select rolname, rolsuper, rolcreatedb from pg_roles where rolname = current_user');
        } catch (Throwable) {
            return null;
        }

        $row = is_object($role) ? get_object_vars($role) : [];

        if (! is_string($row['rolname'] ?? null)) {
            return null;
        }

        // pdo_pgsql returns boolean columns as PHP booleans.
        return [
            'name' => $row['rolname'],
            'superuser' => ($row['rolsuper'] ?? false) === true,
            'createdb' => ($row['rolcreatedb'] ?? false) === true,
        ];
    }
}
