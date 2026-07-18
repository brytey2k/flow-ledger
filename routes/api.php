<?php

declare(strict_types=1);

use App\Http\Controllers\Api\IdpTenantProvisionController;
use Illuminate\Support\Facades\Route;

/*
 * Central-domain API routes. Tenant API routes live in routes/api.tenant.php
 * and are mapped by the TenancyServiceProvider.
 */

Route::post('/idp/tenants', [IdpTenantProvisionController::class, 'store'])
    ->name('api.idp.tenants.store');
