<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ data_get(config('locales.supported'), app()->getLocale() . '.rtl', false) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <title>{{ config('app.name') }} — {{ __('landing.meta.title_suffix') }}</title>
    <meta name="description" content="{{ __('landing.meta.description') }}" />
    <link href="{{ url(request()->path()) }}" rel="canonical" />
    <link href="{{ asset('assets/media/app/favicon-32x32.png') }}" rel="icon" sizes="32x32" type="image/png" />
    <link href="{{ asset('assets/media/app/favicon-16x16.png') }}" rel="icon" sizes="16x16" type="image/png" />

    @fonts
    @vite(['resources/css/landing.css', 'resources/js/app.js'])
</head>
<body>
<header
    class="nav"
    x-data="{ navOpen: false, scrolled: false }"
    x-init="scrolled = window.scrollY > 24; window.addEventListener('scroll', () => scrolled = window.scrollY > 24, { passive: true })"
    :class="{ 'is-scrolled': scrolled }"
    @keydown.escape.window="navOpen = false"
>
    <div class="nav__inner">
        <a class="nav__brand" href="{{ route('landlord.home') }}">
            <img class="nav__logo nav__logo--light" src="{{ asset('assets/media/app/flowledger_logo_light.png') }}" alt="{{ config('app.name') }}" />
            <img class="nav__logo nav__logo--dark" src="{{ asset('assets/media/app/flowledger_logo_dark.png') }}" alt="{{ config('app.name') }}" />
        </a>

        <nav class="nav__center" aria-label="Primary">
            <a class="nav__link" href="#workflow">{{ __('landing.nav.how_it_works') }}</a>
            <a class="nav__link" href="#security">{{ __('landing.nav.security') }}</a>
        </nav>

        <div class="nav__right">
            <form class="nav__locale" method="POST" action="{{ route('landlord.locale.update') }}">
                @csrf
                <label class="sr-only" for="landing-locale">{{ __('landing.nav.language') }}</label>
                <select id="landing-locale" name="locale" class="locale-select" onchange="this.form.submit()">
                    @foreach (config('locales.supported') as $code => $meta)
                        <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $meta['native_label'] }}</option>
                    @endforeach
                </select>
            </form>
            <a class="btn btn--fill" href="{{ route('landlord.register') }}">{{ __('landing.nav.get_started') }}</a>
        </div>

        <button
            class="nav__toggle"
            type="button"
            aria-label="{{ __('landing.nav.toggle_menu') }}"
            :aria-expanded="navOpen.toString()"
            @click="navOpen = !navOpen"
        ><span></span></button>
    </div>

    <div class="nav__sheet" x-show="navOpen" x-cloak x-transition.duration.150ms>
        <a class="nav__link" href="#workflow" @click="navOpen = false">{{ __('landing.nav.how_it_works') }}</a>
        <a class="nav__link" href="#security" @click="navOpen = false">{{ __('landing.nav.security') }}</a>
        <a class="btn btn--fill" href="{{ route('landlord.register') }}">{{ __('landing.nav.get_started') }}</a>
        <form class="nav__locale nav__locale--sheet" method="POST" action="{{ route('landlord.locale.update') }}">
            @csrf
            <label class="sr-only" for="landing-locale-mobile">{{ __('landing.nav.language') }}</label>
            <select id="landing-locale-mobile" name="locale" class="locale-select" onchange="this.form.submit()">
                @foreach (config('locales.supported') as $code => $meta)
                    <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $meta['native_label'] }}</option>
                @endforeach
            </select>
        </form>
    </div>
</header>

<main>
    <section class="hero">
        <div class="container hero-split">
            <div class="hero__inner">
                <span class="hero__badge">
                    <span class="hero__badge-dot"></span>
                    {{ __('landing.hero.badge') }}
                </span>
                <h1 class="hero__headline">{{ __('landing.hero.headline') }}</h1>
                <p class="hero__lede">{{ __('landing.hero.lede') }}</p>
                <div class="hero__ctas">
                    <a class="btn btn--fill" href="{{ route('landlord.register') }}">{{ __('landing.hero.get_started') }}</a>
                    <a class="btn btn--outline" href="#workflow">{{ __('landing.hero.see_how_it_works') }}</a>
                </div>
                <p class="hero__note">{{ __('landing.hero.note') }}</p>
            </div>

            <div class="hero__shot">
                <div class="hero__shot-bar">
                    <span class="hero__shot-dot"></span>
                    <span class="hero__shot-dot"></span>
                    <span class="hero__shot-dot"></span>
                    <span class="hero__shot-domain">{{ parse_url(config('app.url'), PHP_URL_HOST) }}</span>
                </div>
                <div class="hero__mock" role="img" aria-label="{{ __('landing.hero.mock_aria') }}">
                    <div class="hero__mock-nav">
                        <div class="hero__mock-nav-item is-active">{{ __('landing.hero.mock_nav_dashboard') }}</div>
                        <div class="hero__mock-nav-item">{{ __('landing.hero.mock_nav_requests') }}</div>
                        <div class="hero__mock-nav-item">{{ __('landing.hero.mock_nav_workflows') }}</div>
                        <div class="hero__mock-nav-item">{{ __('landing.hero.mock_nav_cashbook') }}</div>
                    </div>
                    <div class="hero__mock-main">
                        <div class="hero__mock-kpis">
                            <div class="hero__mock-kpi">
                                <span class="hero__mock-kpi-label">{{ __('landing.hero.mock_kpi_pending') }}</span>
                                <span class="hero__mock-kpi-value">14</span>
                            </div>
                            <div class="hero__mock-kpi">
                                <span class="hero__mock-kpi-label">{{ __('landing.hero.mock_kpi_approved') }}</span>
                                <span class="hero__mock-kpi-value">6</span>
                            </div>
                            <div class="hero__mock-kpi">
                                <span class="hero__mock-kpi-label">{{ __('landing.hero.mock_kpi_disbursed') }}</span>
                                <span class="hero__mock-kpi-value">GH₵48.2k</span>
                            </div>
                        </div>
                        <div class="hero__mock-bars">
                            @foreach ([46, 62, 38, 71, 54, 80, 65, 58, 73, 60, 84, 69] as $bar)
                                <span style="height: {{ $bar }}%"></span>
                            @endforeach
                        </div>
                        <div class="hero__mock-rows">
                            <div class="hero__mock-row">
                                <span class="hero__mock-row-avatar">AB</span>
                                <span class="hero__mock-row-name">Accra Branch</span>
                                <span class="hero__mock-row-amount">GH₵1,240.00</span>
                                <span class="hero__mock-row-pill">{{ __('landing.hero.mock_status_approved') }}</span>
                            </div>
                            <div class="hero__mock-row">
                                <span class="hero__mock-row-avatar">KB</span>
                                <span class="hero__mock-row-name">Kumasi Branch</span>
                                <span class="hero__mock-row-amount">GH₵860.00</span>
                                <span class="hero__mock-row-pill">{{ __('landing.hero.mock_status_approved') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="steps-section" id="workflow">
        <div class="container">
            <div class="section-head">
                <h2>{{ __('landing.steps.heading') }}</h2>
                <p>{{ __('landing.steps.lede') }}</p>
            </div>

            <ol class="steps">
                <li class="step">
                    <span class="step__num">1.0</span>
                    <h3 class="step__heading">{{ __('landing.steps.submit.heading') }}</h3>
                    <p class="step__body">{{ __('landing.steps.submit.body') }}</p>
                </li>
                <li class="step">
                    <span class="step__num">2.0</span>
                    <h3 class="step__heading">{{ __('landing.steps.route.heading') }}</h3>
                    <p class="step__body">{{ __('landing.steps.route.body') }}</p>
                </li>
                <li class="step">
                    <span class="step__num">3.0</span>
                    <h3 class="step__heading">{{ __('landing.steps.release.heading') }}</h3>
                    <p class="step__body">{{ __('landing.steps.release.body') }}</p>
                </li>
                <li class="step">
                    <span class="step__num">4.0</span>
                    <h3 class="step__heading">{{ __('landing.steps.close.heading') }}</h3>
                    <p class="step__body">{{ __('landing.steps.close.body') }}</p>
                </li>
            </ol>
        </div>
    </section>

    <section class="bento-section">
        <div class="container">
            <div class="section-head">
                <h2>{{ __('landing.bento.heading') }}</h2>
                <p>{{ __('landing.bento.lede') }}</p>
            </div>

            <div class="bento">
                <article class="cell">
                    <span class="cell__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"></rect><rect x="14" y="3" width="7" height="5" rx="1.5"></rect><rect x="14" y="12" width="7" height="9" rx="1.5"></rect><rect x="3" y="16" width="7" height="5" rx="1.5"></rect></svg>
                    </span>
                    <div class="cell__body">
                        <h3 class="cell__title">{{ __('landing.bento.dashboard.title') }}</h3>
                        <p class="cell__text">{{ __('landing.bento.dashboard.text') }}</p>
                    </div>
                </article>

                <article class="cell">
                    <span class="cell__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="2.5"></circle><circle cx="6" cy="18" r="2.5"></circle><circle cx="18" cy="12" r="2.5"></circle><path d="M6 8.5v7"></path><path d="M8.3 7.2 15.7 10.8"></path><path d="M8.3 16.8 15.7 13.2"></path></svg>
                    </span>
                    <div class="cell__body">
                        <h3 class="cell__title">{{ __('landing.bento.workflow_stages.title') }}</h3>
                        <p class="cell__text">{{ __('landing.bento.workflow_stages.text') }}</p>
                    </div>
                </article>

                <article class="cell">
                    <span class="cell__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h9l3 3v17H6z"></path><path d="M9 8h6M9 12h6M9 16h3"></path></svg>
                    </span>
                    <div class="cell__body">
                        <h3 class="cell__title">{{ __('landing.bento.payment_requests.title') }}</h3>
                        <p class="cell__text">{{ __('landing.bento.payment_requests.text') }}</p>
                    </div>
                </article>

                <article class="cell">
                    <span class="cell__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"></rect><path d="M3 9h18"></path><path d="M8 13h4"></path></svg>
                    </span>
                    <div class="cell__body">
                        <h3 class="cell__title">{{ __('landing.bento.cashbook.title') }}</h3>
                        <p class="cell__text">{{ __('landing.bento.cashbook.text') }}</p>
                    </div>
                </article>

                <article class="cell">
                    <span class="cell__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                    </span>
                    <div class="cell__body">
                        <h3 class="cell__title">{{ __('landing.bento.retirements.title') }}</h3>
                        <p class="cell__text">{{ __('landing.bento.retirements.text') }}</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="spec-section" id="security">
        <div class="container">
            <div class="section-head">
                <h2>{{ __('landing.spec.heading') }}</h2>
                <p>{{ __('landing.spec.lede') }}</p>
            </div>

            <table class="spec-sheet">
                <tbody>
                    <tr>
                        <th scope="row">{{ __('landing.spec.multi_tenant.term') }}</th>
                        <td>{{ __('landing.spec.multi_tenant.description') }}</td>
                    </tr>
                    <tr>
                        <th scope="row">{{ __('landing.spec.roles_permissions.term') }}</th>
                        <td>{{ __('landing.spec.roles_permissions.description') }}</td>
                    </tr>
                    <tr>
                        <th scope="row">{{ __('landing.spec.sso.term') }}</th>
                        <td>{{ __('landing.spec.sso.description') }}</td>
                    </tr>
                    <tr>
                        <th scope="row">{{ __('landing.spec.activity_log.term') }}</th>
                        <td>{{ __('landing.spec.activity_log.description') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="cta-band-section">
        <div class="container">
            <div class="cta-band">
                <div class="cta-band__text">
                    <h2>{{ __('landing.cta_strip.heading') }}</h2>
                    <p>{{ __('landing.cta_strip.lede') }}</p>
                </div>
                <div class="cta-band__actions">
                    <a class="btn btn--on-accent" href="{{ route('landlord.register') }}">{{ __('landing.cta_strip.get_started') }}</a>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="foot-line">
    <div class="container foot-line__inner">
        <span>{{ config('app.name') }}</span>
        <span class="foot-line__sep">·</span>
        <span>{{ __('landing.footer.tagline') }}</span>
        <span class="foot-line__sep">·</span>
        <span>&copy; {{ now()->year }} {{ config('app.name') }}</span>
    </div>
</footer>
</body>
</html>
