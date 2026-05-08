<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequestItem extends Model
{
    /** @use HasFactory<\Database\Factories\Tenant\PaymentRequestItemFactory> */
    use HasFactory;

    protected $fillable = ['payment_request_id', 'description', 'amount', 'account_code_id', 'receipt_number'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<PaymentRequest, $this> */
    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    /** @return BelongsTo<AccountCode, $this> */
    public function accountCode(): BelongsTo
    {
        return $this->belongsTo(AccountCode::class);
    }
}
