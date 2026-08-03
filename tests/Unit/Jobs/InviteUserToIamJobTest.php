<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Jobs\InviteUserToIamJob;
use App\Models\Tenant\User;
use App\Services\IamUserProvisioningService;

test('handle invites user and persists oidc sub', function () {
    $user = User::factory()->create(['branch_id' => $this->branch->id, 'oidc_sub' => null]);

    $service = $this->createMock(IamUserProvisioningService::class);
    $service->expects($this->once())
        ->method('invite')
        ->with($this->callback(fn(User $u): bool => $u->id === $user->id), 'idp-tenant-1')
        ->willReturn(['iam_user_id' => 'iam-user-1', 'status' => 'created']);

    $job = new InviteUserToIamJob($user->id, 'idp-tenant-1');
    $job->handle($service);

    expect($user->fresh()->oidc_sub)->toBe('iam-user-1');
});

test('handle does nothing when user no longer exists', function () {
    $service = $this->createMock(IamUserProvisioningService::class);
    $service->expects($this->never())->method('invite');

    $job = new InviteUserToIamJob(999999, 'idp-tenant-1');
    $job->handle($service);
});

test('handle skips when idp tenant id is null', function () {
    $user = User::factory()->create(['branch_id' => $this->branch->id]);

    $service = $this->createMock(IamUserProvisioningService::class);
    $service->expects($this->never())->method('invite');

    $job = new InviteUserToIamJob($user->id, null);
    $job->handle($service);
});

test('handle lets service exception bubble for retry', function () {
    $user = User::factory()->create(['branch_id' => $this->branch->id]);

    $service = $this->createMock(IamUserProvisioningService::class);
    $service->method('invite')->willThrowException(new RuntimeException('IDP user invite failed'));

    $job = new InviteUserToIamJob($user->id, 'idp-tenant-1');

    $this->expectException(RuntimeException::class);
    $job->handle($service);
});
