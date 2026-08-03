<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum UserStatus: string
{
    case Invited = 'invited';
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Invited => __('users.status_invited'),
            self::Active => __('users.status_active'),
            self::Suspended => __('users.status_suspended'),
        };
    }
}
