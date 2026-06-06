<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\Tenant\SettingKey;
use App\Models\Tenant\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsRepository
{
    private const CACHE_TAG = 'settings';

    private const CACHE_TTL = 3600;

    /**
     * @param SettingKey $key
     *
     * @return array<string, mixed>|null
     */
    public function get(SettingKey $key): array|null
    {
        return Cache::tags([self::CACHE_TAG])->remember(
            "setting:{$key->value}",
            self::CACHE_TTL,
            fn() => Setting::query()->where('key', $key->value)->first()?->value,
        );
    }

    /**
     * @param SettingKey $key
     * @param array<string, mixed> $value
     */
    public function set(SettingKey $key, array $value): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key->value],
            ['value' => $value],
        );

        $this->clearCache();
    }

    public function clearCache(): void
    {
        Cache::tags([self::CACHE_TAG])->flush();
    }
}
