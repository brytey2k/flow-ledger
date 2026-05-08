<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\Attachment;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    public function definition(): array
    {
        return [
            'attachable_type' => RetirementRequest::class,
            'attachable_id' => RetirementRequest::factory(),
            'user_id' => User::factory(),
            'path' => 'retirements/' . fake()->numberBetween(1, 100) . '/attachments/' . fake()->uuid() . '.pdf',
            'original_name' => fake()->word() . '.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10240, 2097152),
        ];
    }
}
