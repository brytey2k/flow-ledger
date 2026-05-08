<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\PaymentRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentRequestItem>
 */
class PaymentRequestItemFactory extends Factory
{
    protected $model = PaymentRequestItem::class;

    public function definition(): array
    {
        return [
            'payment_request_id' => PaymentRequest::factory(),
            'description' => fake()->sentence(3),
            'amount' => fake()->randomFloat(2, 50, 2000),
        ];
    }
}
