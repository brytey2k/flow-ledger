<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\Branch;
use App\Models\Tenant\CashbookEntry;
use App\Models\Tenant\CashCount;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowAction;
use App\Services\DemoDataService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

test('demo:seed refuses to run outside local, testing and staging without --force', function () {
    app()->detectEnvironment(fn(): string => 'production');

    $this->artisan('demo:seed')
        ->expectsOutputToContain('only runs in local, testing or staging')
        ->assertFailed();
});

test('the demo data service seeds people, workflows and requests in every state, and a rerun adds no duplicates', function () {
    // Same mail setup as demo:seed: queued notification mail runs inline into the in-memory mailer.
    config(['mail.default' => 'array', 'queue.default' => 'sync']);
    Storage::fake('local');

    $result = app(DemoDataService::class)->seed();

    expect($result['created'])->toBeTrue();

    $admin = User::where('email', DemoDataService::ADMIN_EMAIL)->firstOrFail();
    expect(Hash::check(DemoDataService::PASSWORD, $admin->password))->toBeTrue()
        ->and($admin->hasRole(config()->string('roles.system_admin_name')))->toBeTrue()
        ->and($admin->staffProfile)->not->toBeNull()
        ->and(User::where('email', 'efua.owusu@demo.test')->firstOrFail()->hasRole('Finance Director'))->toBeTrue()
        ->and(Branch::whereIn('code', ['HO', 'ACC', 'KSI', 'TML', 'TKD'])->count())->toBe(5);

    $statuses = PaymentRequest::pluck('status')->unique()->sort()->values()->all();
    expect($statuses)->toEqualCanonicalizing(['approved', 'cancelled', 'denied', 'disbursed', 'draft', 'in_workflow', 'retired', 'sent_back'])
        ->and(PaymentRequest::count())->toBe(25)
        ->and(PaymentRequest::whereNotNull('disbursement_reference')->count())->toBe(13)
        ->and(RetirementRequest::where('status', 'settled')->pluck('difference_type')->sort()->values()->all())
        ->toBe(['nil', 'pay_to_staff', 'refund_to_company'])
        ->and(WorkflowAction::where('action', 'send_back')->count())->toBe(3)
        ->and(CashbookEntry::where('sourceable_type', PaymentRequest::class)->count())->toBe(13)
        ->and(CashCount::count())->toBe(2)
        ->and(PaymentRequest::where('status', 'disbursed')->min('disbursed_at'))->toBeLessThan(now()->subDays(30)->toDateTimeString())
        ->and(Mail::mailer('array')->getSymfonyTransport()->messages())->not->toBeEmpty();

    expect(app(DemoDataService::class)->seed()['created'])->toBeFalse()
        ->and(PaymentRequest::count())->toBe(25);
});
