<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Permission\PermissionRegistrar;

/**
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read int|null $users_count
 *
 * @mixin \Eloquent
 */
class Role extends \Spatie\Permission\Models\Role
{
    /**
     * Override Spatie's default users() to always resolve to the tenant User
     * model. The parent implementation calls getModelForGuard() which may
     * resolve to App\Models\Landlord\User (CentralConnection) instead of
     * App\Models\Tenant\User, causing cross-database queries when the Role
     * is queried on a tenant connection.
     *
     * @return MorphToMany<User, $this, \Illuminate\Database\Eloquent\Relations\MorphPivot, 'pivot'>
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            User::class,
            'model',
            config()->string('permission.table_names.model_has_roles'),
            app(PermissionRegistrar::class)->pivotRole,
            config()->string('permission.column_names.model_morph_key'),
        );
    }
}
