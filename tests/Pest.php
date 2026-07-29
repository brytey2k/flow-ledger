<?php

declare(strict_types=1);

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Every other test file explicitly binds its own base class via a per-file uses() call
| (TenantAppTestCase, LandlordTestCase, ApiTenantTestCase, or Tests\TestCase). Pest does
| not allow a directory-wide default to also match a file that declares its own uses(),
| so this default is scoped to just the Unit test files that don't declare one.
|
*/

pest()->extend(TestCase::class)->in(
    'Unit/ExampleTest.php',
    'Unit/Bootstrap/TrustProxiesHostHeaderTest.php',
    'Unit/Enums/PaymentRequestStatusTest.php',
    'Unit/Enums/PermissionKeyTest.php',
    'Unit/Enums/CurrencyDenominationTypeTest.php',
    'Unit/Enums/PaymentMethodTest.php',
    'Unit/Enums/PaymentRequestTypeTest.php',
    'Unit/Enums/FeatureFlagTest.php',
    'Unit/Enums/SettingKeyTest.php',
    'Unit/Features/FeatureClassesTest.php',
    'Unit/Support/PhoneNumberFormatterTest.php',
    'Unit/Support/CsvSanitizerTest.php',
    'Unit/Helpers/CurrencyHelperTest.php',
    'Unit/Services/IdpTenantProvisionReporterServiceTest.php',
    'Unit/Services/IdpTenantServiceTest.php',
);
