<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Tests\TenantAppTestCase;

class SetLocaleTest extends TenantAppTestCase
{
    public function test_locale_is_set_from_session(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['locale' => 'fr'])
            ->get(route('dashboard'));

        $response->assertOk();
        $this->assertSame('fr', app()->getLocale());
    }

    public function test_locale_defaults_to_app_locale_when_session_and_user_locale_absent(): void
    {
        $this->user->update(['locale' => null]);

        $response = $this->actingAs($this->user)
            ->withSession([])
            ->get(route('dashboard'));

        $response->assertOk();
        $this->assertSame(config('app.locale', 'en'), app()->getLocale());
    }

    public function test_session_locale_takes_precedence_over_user_locale(): void
    {
        $this->user->update(['locale' => 'en']);

        $response = $this->actingAs($this->user)
            ->withSession(['locale' => 'fr'])
            ->get(route('dashboard'));

        $response->assertOk();
        $this->assertSame('fr', app()->getLocale());
    }

    public function test_user_locale_is_used_when_no_session_locale(): void
    {
        $this->user->update(['locale' => 'en']);

        $response = $this->actingAs($this->user)
            ->withSession([])
            ->get(route('dashboard'));

        $response->assertOk();
        $this->assertSame('en', app()->getLocale());
    }
}
