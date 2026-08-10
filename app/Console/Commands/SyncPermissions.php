<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Tenant\PermissionKey;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Concerns\HasATenantsOption;

#[Signature('permissions:sync {--prune : Delete DB permissions not present in the enum}')]
#[Description('Sync all PermissionKey enum cases into the Spatie permissions table for each tenant')]
class SyncPermissions extends Command
{
    use HasATenantsOption;

    public function handle(PermissionRegistrar $permissionRegistrar): int
    {
        $this->getTenants()->each(function (mixed $tenant) use ($permissionRegistrar): void {
            assert($tenant instanceof Tenant);
            tenancy()->initialize($tenant);

            $this->newLine();
            $this->line("── Tenant: <fg=cyan>{$tenant->id}</>");

            $permissionRegistrar->forgetCachedPermissions();

            $created = 0;
            $existing = 0;
            $rows = [];
            $newlyCreatedNames = [];

            foreach (PermissionKey::cases() as $case) {
                $permission = Permission::firstOrCreate([
                    'name' => $case->value,
                    'guard_name' => 'web',
                ]);

                if ($permission->wasRecentlyCreated) {
                    $created++;
                    $newlyCreatedNames[] = $case->value;
                    $rows[] = [$case->name, $case->value, '<fg=green>Created</>'];
                } else {
                    $existing++;
                    $rows[] = [$case->name, $case->value, '<fg=gray>Exists</>'];
                }
            }

            $this->table(['Enum Case', 'Permission', 'Status'], $rows);
            $this->info("Created: {$created}, Already existed: {$existing}.");

            $this->grantNewPermissionsToSystemAdmin($newlyCreatedNames);

            if ($this->option('prune')) {
                $this->pruneOrphaned($permissionRegistrar);
            }

            $permissionRegistrar->forgetCachedPermissions();

            tenancy()->end();
        });

        return self::SUCCESS;
    }

    /**
     * Grant only the permissions newly created in this run to the system admin
     * role, so they remain assignable to someone. Deliberately does NOT sync
     * the role to the full permission set: a permission it doesn't hold may
     * have been intentionally revoked from it previously, and re-adding it
     * here would silently undo that.
     *
     * @param  list<string>  $newlyCreatedNames
     */
    private function grantNewPermissionsToSystemAdmin(array $newlyCreatedNames): void
    {
        if ($newlyCreatedNames === []) {
            return;
        }

        try {
            $systemAdminRole = Role::findByName(config()->string('roles.system_admin_name'), 'web');
        } catch (RoleDoesNotExist) {
            $this->warn('No system admin role found for this tenant — skipping permission grant.');

            return;
        }

        $systemAdminRole->givePermissionTo($newlyCreatedNames);

        $this->info('Granted newly created permissions to the system admin role: '.implode(', ', $newlyCreatedNames));
    }

    private function pruneOrphaned(PermissionRegistrar $permissionRegistrar): void
    {
        $validValues = array_column(PermissionKey::cases(), 'value');

        $orphans = Permission::whereNotIn('name', $validValues)->get();

        if ($orphans->isEmpty()) {
            $this->line('No orphaned permissions found.');

            return;
        }

        Permission::whereIn('id', $orphans->pluck('id'))->delete();

        foreach ($orphans as $orphan) {
            $this->warn("Pruned: [{$orphan->name}]");
        }

        $this->info("Pruned {$orphans->count()} orphaned permission(s).");

        $permissionRegistrar->forgetCachedPermissions();
    }
}
