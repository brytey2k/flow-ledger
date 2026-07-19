<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Role;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashbookEntry;
use App\Models\Tenant\CashCount;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class MorphMapServiceProvider extends ServiceProvider
{
    /**
     * PaymentRequest and RetirementRequest are deliberately excluded: they're also morph
     * targets of Attachment::attachable, Comment::commentable, WorkflowInstance::workflowable,
     * and CashbookEntry::sourceable, some of which are compared against raw FQCN strings
     * elsewhere (e.g. AttachmentsController). Aliasing them here would silently break those.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'user' => User::class,
            'staff' => Staff::class,
            'cashbook' => Cashbook::class,
            'cashbook_entry' => CashbookEntry::class,
            'cash_count' => CashCount::class,
            'role' => Role::class,
            'branch' => Branch::class,
        ]);
    }
}
