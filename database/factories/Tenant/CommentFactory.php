<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Models\Tenant\Comment;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'commentable_type' => PaymentRequest::class,
            'commentable_id' => PaymentRequest::factory(),
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
            'workflow_instance_stage_id' => null,
        ];
    }
}
