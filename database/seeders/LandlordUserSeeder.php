<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Landlord\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LandlordUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@flow-ledger.test'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
    }
}
