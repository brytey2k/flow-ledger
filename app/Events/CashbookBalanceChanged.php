<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Tenant\Cashbook;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CashbookBalanceChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Cashbook $cashbook,
        public readonly float $previousBalance,
        public readonly float $newBalance,
    ) {}
}
