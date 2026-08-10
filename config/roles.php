<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | System Admin Role Name
    |--------------------------------------------------------------------------
    |
    | The tenant role that automatically receives newly introduced permission
    | keys when they ship via continuous delivery (see SyncPermissions). This
    | is also the role seeded with every permission at tenant creation time
    | (see NewTenantSetupService).
    |
    */
    'system_admin_name' => env('SYSTEM_ADMIN_ROLE_NAME', 'admin'),
];
