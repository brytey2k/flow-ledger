<?php

declare(strict_types=1);

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class User extends Authenticatable
{
    use CentralConnection;
    /** @use HasFactory<\Database\Factories\Landlord\UserFactory> */
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'landlord_users';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getLocaleAttribute(): string|null
    {
        return null;
    }
}
