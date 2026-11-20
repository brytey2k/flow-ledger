@php
    // Each step's screenshot (public/assets/media/landing/{name}-{light|dark}.webp) and its size.
    $steps = [
        'request' => ['request-draft', 1400, 996],
        'approve' => ['approval-review', 1400, 996],
        'disburse' => ['disbursements', 1400, 996],
        'retire' => ['retirement-form', 1600, 1104],
        'report' => ['outstanding-advances', 1400, 996],
    ];
    $controls = ['segregation', 'audit', 'branches', 'roles', 'sso', 'isolation', 'export', 'currency'];
    $promoSeconds = $promo === null ? null : trans_choice('landing.film.seconds', $promo['seconds'], ['count' => $promo['seconds']]);
    $promoClock = $promo === null ? null : sprintf('%d:%02d', intdiv($promo['seconds'], 60), $promo['seconds'] % 60);
    $shot = static fn (string $name, string $theme): string => asset("assets/media/landing/{$name}-{$theme}.webp");
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ data_get(config('locales.supported'), app()->getLocale() . '.rtl', false) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <title>{{ config('app.name') }} — {{ __('landing.meta.title_suffix') }}</title>
    <meta name="description" content="{{ __('landing.meta.description') }}" />
    <link href="{{ url(request()->path()) }}" rel="canonical" />
    <link href="{{ asset('assets/media/app/favicon.ico') }}?v=3" rel="icon" type="image/x-icon" />
    <link href="{{ asset('assets/media/app/favicon-32x32.png') }}?v=3" rel="icon" sizes="32x32" type="image/png" />
    <link href="{{ asset('assets/media/app/apple-touch-icon.png') }}?v=3" rel="apple-touch-icon" />
    <meta property="og:title" content="{{ config('app.name') }} — {{ __('landing.meta.title_suffix') }}" />
    <meta property="og:description" content="{{ __('landing.meta.description') }}" />
    <meta property="og:image" content="{{ asset('assets/media/app/og-image.png') }}" />

    @fonts(['geist', 'sora'])
    @vite(['resources/css/app.css', 'resources/css/landing.css', 'resources/js/app.js'])
</head>
<body class="lp">
@include('tenant.partials.theme-toggle')

<a class="lp-skip" href="#main">{{ __('landing.nav.skip') }}</a>

<header class="lp-nav" x-data="{ open: false }" @keydown.escape.window="open = false">
    <div class="lp-nav__bar">
        <a class="lp-nav__brand" href="{{ route('landlord.home') }}" aria-label="{{ __('landing.nav.home') }}">
            <img class="dark:hidden" src="{{ asset('assets/media/app/flowledger_logo_light.png') }}" alt="{{ config('app.name') }}" width="126" height="22" />
            <img class="hidden dark:block" src="{{ asset('assets/media/app/flowledger_logo_dark.png') }}" alt="{{ config('app.name') }}" width="126" height="22" />
        </a>

        <nav class="lp-nav__links" aria-label="{{ __('landing.nav.primary') }}">
            <a href="#how-it-works">{{ __('landing.nav.how_it_works') }}</a>
            <a href="#controls">{{ __('landing.nav.security') }}</a>
            @if ($promo !== null)
                <a href="#film">{{ __('landing.nav.film') }}</a>
            @endif
            <a href="{{ route('landlord.docs') }}">{{ __('landing.nav.user_guide') }}</a>
        </nav>

        <div class="lp-nav__tools">
            <form class="lp-nav__locale" method="POST" action="{{ route('landlord.locale.update') }}">
                @csrf
                <label class="sr-only" for="landing-locale">{{ __('landing.nav.language') }}</label>
                <select id="landing-locale" name="locale" class="lp-select" onchange="this.form.submit()">
                    @foreach (config('locales.supported') as $code => $meta)
                        <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $meta['native_label'] }}</option>
                    @endforeach
                </select>
            </form>
            <div x-data="themeToggle">
                <button type="button" class="lp-icon-btn" @click="toggleTheme" :aria-pressed="dark.toString()" aria-label="{{ __('landing.nav.dark_mode') }}">
                    <x-tabler-moon-filled class="size-4" x-show="! dark" aria-hidden="true" />
                    <x-tabler-sun-filled class="size-4" x-show="dark" x-cloak aria-hidden="true" />
                </button>
            </div>
            <a class="lp-btn lp-btn--primary lp-btn--sm" href="#contact">{{ __('landing.nav.book_demo') }}</a>
            <button
                type="button"
                class="lp-icon-btn lp-nav__toggle"
                aria-controls="landing-menu"
                :aria-expanded="open.toString()"
                aria-label="{{ __('landing.nav.toggle_menu') }}"
                @click="open = ! open"
            >
                <x-tabler-menu-2 class="size-5" x-show="! open" aria-hidden="true" />
                <x-tabler-x class="size-5" x-show="open" x-cloak aria-hidden="true" />
            </button>
        </div>
    </div>

    <div class="lp-nav__sheet" id="landing-menu" x-show="open" x-cloak @click.outside="open = false">
        <a href="#how-it-works" @click="open = false">{{ __('landing.nav.how_it_works') }}</a>
        <a href="#controls" @click="open = false">{{ __('landing.nav.security') }}</a>
        @if ($promo !== null)
            <a href="#film" @click="open = false">{{ __('landing.nav.film') }}</a>
        @endif
        <a href="{{ route('landlord.docs') }}">{{ __('landing.nav.user_guide') }}</a>
        <a class="lp-nav__sheet-cta" href="#contact" @click="open = false">{{ __('landing.nav.book_demo') }}</a>
        <form method="POST" action="{{ route('landlord.locale.update') }}">
            @csrf
            <label class="sr-only" for="landing-locale-menu">{{ __('landing.nav.language') }}</label>
            <select id="landing-locale-menu" name="locale" class="lp-select" onchange="this.form.submit()">
                @foreach (config('locales.supported') as $code => $meta)
                    <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $meta['native_label'] }}</option>
                @endforeach
            </select>
        </form>
    </div>
</header>

<main id="main">
    <section class="lp-hero" aria-labelledby="hero-heading">
        <div class="lp-container lp-hero__grid">
            <div class="lp-hero__copy">
                <h1 class="lp-display" id="hero-heading">{{ __('landing.hero.headline') }}</h1>
                <p class="lp-hero__lede">{{ __('landing.hero.lede') }}</p>
                <div class="lp-actions">
                    <a class="lp-btn lp-btn--primary" href="#contact">{{ __('landing.hero.book_demo') }}</a>
                    @if ($promo !== null)
                        <a class="lp-btn lp-btn--outline" href="#film">
                            <x-tabler-player-play-filled class="size-4" aria-hidden="true" />
                            {{ __('landing.hero.watch_film') }} <span class="lp-btn__meta">{{ $promoClock }}</span>
                        </a>
                    @else
                        <a class="lp-btn lp-btn--outline" href="#how-it-works">{{ __('landing.hero.see_how_it_works') }}</a>
                    @endif
                </div>
                <p class="lp-hero__call">
                    {{ __('landing.hero.call_prompt') }}
                    <a href="{{ $salesPhoneLink }}">{{ $salesPhone }}</a>
                </p>
            </div>

            <figure class="lp-hero__shot">
                <div class="lp-shot lp-shot--hero">
                    <img class="dark:hidden" src="{{ $shot('dashboard', 'light') }}" alt="{{ __('landing.hero.shot_alt') }}" width="2000" height="1250" fetchpriority="high" />
                    <img class="hidden dark:block" src="{{ $shot('dashboard', 'dark') }}" alt="{{ __('landing.hero.shot_alt') }}" width="2000" height="1250" loading="lazy" />
                </div>
                <figcaption>{{ __('landing.hero.shot_caption') }}</figcaption>
            </figure>
        </div>
    </section>

    <section class="lp-section" id="problem" aria-labelledby="problem-heading">
        <div class="lp-container">
            <div class="lp-head">
                <h2 class="lp-h2" id="problem-heading">{{ __('landing.problem.heading') }}</h2>
                <p>{{ __('landing.problem.lede') }}</p>
            </div>

            <div class="lp-ledger">
                <div class="lp-ledger__cols" aria-hidden="true">
                    <span>{{ __('landing.problem.without') }}</span>
                    <span>{{ __('landing.problem.with') }}</span>
                </div>
                <ul role="list">
                    @foreach (['signatures', 'unretired', 'cash', 'trail'] as $row)
                        <li class="lp-ledger__row">
                            <p class="lp-ledger__problem">
                                <span class="lp-ledger__label">{{ __('landing.problem.without') }}</span>
                                {{ __("landing.problem.rows.{$row}.problem") }}
                            </p>
                            <p class="lp-ledger__answer">
                                <span class="lp-ledger__label">{{ __('landing.problem.with') }}</span>
                                <span class="lp-ledger__answer-body">
                                    <x-tabler-circle-check-filled class="lp-ledger__mark" aria-hidden="true" />
                                    <span>{{ __("landing.problem.rows.{$row}.answer") }}</span>
                                </span>
                            </p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    @if ($promo !== null)
        <section class="lp-section lp-film" id="film" aria-labelledby="film-heading">
            <div class="lp-container lp-film__grid">
                <div class="lp-film__copy">
                    <h2 class="lp-h2" id="film-heading">{{ __('landing.film.heading', ['length' => $promoSeconds]) }}</h2>
                    <p>{{ __('landing.film.lede') }}</p>
                    <ul class="lp-film__links" role="list">
                        <li>
                            <a class="lp-link" href="{{ route('landlord.docs.videos') }}">
                                <x-tabler-movie class="size-4" aria-hidden="true" />{{ $hasWalkthrough ? __('landing.film.walkthrough') : __('landing.film.more_videos') }}
                            </a>
                        </li>
                        <li>
                            <a class="lp-link" href="{{ route('landlord.docs') }}">
                                <x-tabler-book-2 class="size-4" aria-hidden="true" />{{ __('landing.film.user_guide') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="lp-film__player">
                    @include('docs.partials.video-player', ['video' => $promo, 'guide' => $guide])
                </div>
            </div>
        </section>
    @endif

    <section class="lp-section" id="how-it-works" aria-labelledby="steps-heading">
        <div class="lp-container">
            <div class="lp-head">
                <h2 class="lp-h2" id="steps-heading">{{ __('landing.steps.heading') }}</h2>
                <p>{{ __('landing.steps.lede') }}</p>
            </div>

            <ol class="lp-steps" role="list">
                @foreach ($steps as $step => [$image, $width, $height])
                    <li class="lp-step">
                        <div class="lp-step__copy">
                            <span class="lp-step__num" aria-hidden="true">{{ $loop->iteration }}</span>
                            <h3 class="lp-h3">{{ __("landing.steps.{$step}.title") }}</h3>
                            <p>{{ __("landing.steps.{$step}.body") }}</p>
                            <ul class="lp-points" role="list">
                                @foreach (__("landing.steps.{$step}.points") as $point)
                                    <li><x-tabler-check class="lp-points__mark" aria-hidden="true" />{{ $point }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <figure class="lp-step__shot">
                            <div class="lp-shot">
                                <img class="dark:hidden" src="{{ $shot($image, 'light') }}" alt="{{ __("landing.steps.{$step}.alt") }}" width="{{ $width }}" height="{{ $height }}" loading="lazy" />
                                <img class="hidden dark:block" src="{{ $shot($image, 'dark') }}" alt="{{ __("landing.steps.{$step}.alt") }}" width="{{ $width }}" height="{{ $height }}" loading="lazy" />
                            </div>
                        </figure>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="lp-section lp-controls" id="controls" aria-labelledby="controls-heading">
        <div class="lp-container">
            <div class="lp-controls__grid">
                <div class="lp-controls__aside">
                    <div class="lp-head">
                        <h2 class="lp-h2" id="controls-heading">{{ __('landing.controls.heading') }}</h2>
                        <p>{{ __('landing.controls.lede') }}</p>
                    </div>
                    <figure class="lp-controls__shot">
                        <div class="lp-shot">
                            <img class="dark:hidden" src="{{ $shot('timeline', 'light') }}" alt="{{ __('landing.controls.shot_alt') }}" width="1200" height="1047" loading="lazy" />
                            <img class="hidden dark:block" src="{{ $shot('timeline', 'dark') }}" alt="{{ __('landing.controls.shot_alt') }}" width="1200" height="1047" loading="lazy" />
                        </div>
                        <figcaption>{{ __('landing.controls.shot_caption') }}</figcaption>
                    </figure>
                </div>

                <dl class="lp-faq">
                    @foreach ($controls as $item)
                        <div class="lp-faq__item">
                            <dt>{{ __("landing.controls.items.{$item}.question") }}</dt>
                            <dd>{{ __("landing.controls.items.{$item}.answer") }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </section>

    <section class="lp-section lp-contact-section" id="contact" aria-labelledby="contact-heading">
        <div class="lp-container">
            <div class="lp-contact">
                <div class="lp-contact__copy">
                    <h2 class="lp-h2" id="contact-heading">{{ __('landing.contact.heading') }}</h2>
                    <p>{{ __('landing.contact.lede') }}</p>
                    <div class="lp-actions">
                        <a class="lp-btn lp-btn--primary" href="{{ $salesPhoneLink }}">
                            <x-tabler-phone class="size-4" aria-hidden="true" />{{ __('landing.contact.call', ['phone' => $salesPhone]) }}
                        </a>
                        <a class="lp-btn lp-btn--on-dark" href="{{ $salesWhatsAppLink }}" rel="noopener noreferrer" target="_blank">
                            <x-tabler-brand-whatsapp class="size-4" aria-hidden="true" />{{ __('landing.contact.whatsapp') }}
                        </a>
                    </div>
                </div>
                <div class="lp-contact__next">
                    <h3 class="lp-contact__next-heading">{{ __('landing.contact.next_heading') }}</h3>
                    <ol role="list">
                        @foreach (['demo', 'workspace', 'setup'] as $next)
                            <li><span class="lp-contact__num" aria-hidden="true">{{ $loop->iteration }}</span>{{ __("landing.contact.next.{$next}") }}</li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="lp-footer">
    <div class="lp-container">
        <p class="lp-footer__statement">{{ __('landing.footer.statement') }}</p>
        <div class="lp-footer__row">
            <img class="lp-footer__logo dark:hidden" src="{{ asset('assets/media/app/flowledger_logo_light.png') }}" alt="{{ config('app.name') }}" width="126" height="22" loading="lazy" />
            <img class="lp-footer__logo hidden dark:block" src="{{ asset('assets/media/app/flowledger_logo_dark.png') }}" alt="{{ config('app.name') }}" width="126" height="22" loading="lazy" />
            <nav class="lp-footer__links" aria-label="{{ __('landing.nav.footer') }}">
                <a href="#how-it-works">{{ __('landing.nav.how_it_works') }}</a>
                <a href="#controls">{{ __('landing.nav.security') }}</a>
                <a href="{{ route('landlord.docs') }}">{{ __('landing.nav.user_guide') }}</a>
                @if ($hasVideos)
                    <a href="{{ route('landlord.docs.videos') }}">{{ __('landing.nav.videos') }}</a>
                @endif
                <a href="{{ $salesPhoneLink }}">{{ $salesPhone }}</a>
            </nav>
            <span class="lp-footer__copy">{{ __('landing.footer.copyright', ['year' => now()->year]) }}</span>
        </div>
    </div>
</footer>
</body>
</html>
