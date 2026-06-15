<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Landlord\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('app:create-super-admin')]
#[Description('Create a central super admin user in the landlord area')]
class CreateSuperAdmin extends Command
{
    public function handle(): int
    {
        $name = text(label: 'Name', required: true);
        $email = text(
            label: 'Email address',
            required: true,
            validate: fn(string $value) => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Please enter a valid email address.',
        );
        $userPassword = password(label: 'Password', required: true);

        if (User::where('email', $email)->exists()) {
            $this->error("A user with email [{$email}] already exists.");

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $userPassword,
            'email_verified_at' => now(),
        ]);

        $this->newLine();
        $this->info('Super admin created successfully.');
        $this->line("  Name:  <fg=cyan>{$name}</>");
        $this->line("  Email: <fg=cyan>{$email}</>");

        return self::SUCCESS;
    }
}
