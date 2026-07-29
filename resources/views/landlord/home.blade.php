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
<div x-data="{ navOpen: false }" @keydown.escape.window="navOpen = false">
    <header class="nav-pill">
        <div class="nav-pill__inner">
            <a class="nav-pill__brand" href="{{ route('landlord.home') }}">
                <img src="{{ asset('assets/media/app/flowledger_logo_light.png') }}" alt="{{ config('app.name') }}" />
            </a>

            <nav class="nav-pill__links" aria-label="Primary">
                <a class="nav-pill__link" href="#workflow">{{ __('landing.nav.how_it_works') }}</a>
                <a class="nav-pill__link" href="#security">{{ __('landing.nav.security') }}</a>
            </nav>

            <div class="nav-pill__actions">
                <form class="nav-pill__locale" method="POST" action="{{ route('landlord.locale.update') }}">
                    @csrf
                    <label class="sr-only" for="landing-locale">{{ __('landing.nav.language') }}</label>
                    <select id="landing-locale" name="locale" onchange="this.form.submit()">
                        @foreach (config('locales.supported') as $code => $meta)
                            <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $meta['native_label'] }}</option>
                        @endforeach
                    </select>
                </form>
                <a class="btn btn--accent" href="{{ route('landlord.register') }}">{{ __('landing.nav.get_started') }}</a>
            </div>

            <button
                class="nav-pill__toggle"
                type="button"
                aria-label="{{ __('landing.nav.toggle_menu') }}"
                :aria-expanded="navOpen.toString()"
                @click="navOpen = !navOpen"
            ><span></span></button>
        </div>

        <div class="nav-pill__sheet" x-show="navOpen" x-cloak x-transition.duration.150ms>
            <a class="nav-pill__link" href="#workflow" @click="navOpen = false">{{ __('landing.nav.how_it_works') }}</a>
            <a class="nav-pill__link" href="#security" @click="navOpen = false">{{ __('landing.nav.security') }}</a>
            <div class="nav-pill__actions">
                <form class="nav-pill__locale" method="POST" action="{{ route('landlord.locale.update') }}">
                    @csrf
                    <label class="sr-only" for="landing-locale-mobile">{{ __('landing.nav.language') }}</label>
                    <select id="landing-locale-mobile" name="locale" onchange="this.form.submit()">
                        @foreach (config('locales.supported') as $code => $meta)
                            <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $meta['native_label'] }}</option>
                        @endforeach
                    </select>
                </form>
                <a class="btn btn--accent" href="{{ route('landlord.register') }}">{{ __('landing.nav.get_started') }}</a>
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container hero-split">
                <div>
                    <h1 class="hero__headline">{{ __('landing.hero.headline') }}</h1>
                    <p class="hero__lede">
                        {{ __('landing.hero.lede') }}
                    </p>
                    <div class="hero__ctas">
                        <a class="btn btn--accent" href="{{ route('landlord.register') }}">{{ __('landing.hero.get_started') }}</a>
                        <a class="btn btn--outline" href="#workflow">{{ __('landing.hero.see_how_it_works') }}</a>
                    </div>
                    <p class="hero__note">{{ __('landing.hero.note') }}</p>
                </div>

                <figure class="hero__figure">
                    <div class="hero__shot">
                        <a class="glightbox" href="{{ asset('images/marketing/dashboard.jpg') }}" data-glightbox="title: {{ __('landing.hero.figure_alt') }}">
                            <img
                                src="{{ asset('images/marketing/dashboard.jpg') }}"
                                alt="{{ __('landing.hero.figure_alt') }}"
                                width="1568"
                                height="689"
                                fetchpriority="high"
                            />
                        </a>
                    </div>
                    <figcaption>{{ __('landing.hero.figure_caption') }}</figcaption>
                </figure>
            </div>
        </section>

        <section class="steps-section" id="workflow">
            <div class="container">
                <div class="steps-head">
                    <h2>{{ __('landing.steps.heading') }}</h2>
                    <p>{{ __('landing.steps.lede') }}</p>
                </div>

                <ol class="steps">
                    <li class="step">
                        <div class="step__text">
                            <span class="step__num">1.0</span>
                            <h3 class="step__heading">{{ __('landing.steps.submit.heading') }}</h3>
                            <p class="step__body">{{ __('landing.steps.submit.body') }}</p>
                        </div>
                        <figure class="step__figure">
                            <div class="step__shot">
                                <a class="glightbox" href="{{ asset('images/marketing/payment-requests.jpg') }}" data-glightbox="title: {{ __('landing.steps.submit.alt') }}">
                                    <img src="{{ asset('images/marketing/payment-requests.jpg') }}" alt="{{ __('landing.steps.submit.alt') }}" width="1568" height="689" loading="lazy" />
                                </a>
                            </div>
                        </figure>
                    </li>
                    <li class="step">
                        <div class="step__text">
                            <span class="step__num">2.0</span>
                            <h3 class="step__heading">{{ __('landing.steps.route.heading') }}</h3>
                            <p class="step__body">{{ __('landing.steps.route.body') }}</p>
                        </div>
                        <figure class="step__figure">
                            <div class="step__shot">
                                <a class="glightbox" href="{{ asset('images/marketing/workflow-stages.jpg') }}" data-glightbox="title: {{ __('landing.steps.route.alt') }}">
                                    <img src="{{ asset('images/marketing/workflow-stages.jpg') }}" alt="{{ __('landing.steps.route.alt') }}" width="1568" height="689" loading="lazy" />
                                </a>
                            </div>
                        </figure>
                    </li>
                    <li class="step">
                        <div class="step__text">
                            <span class="step__num">3.0</span>
                            <h3 class="step__heading">{{ __('landing.steps.release.heading') }}</h3>
                            <p class="step__body">{{ __('landing.steps.release.body') }}</p>
                        </div>
                        <figure class="step__figure">
                            <div class="step__shot">
                                <a class="glightbox" href="{{ asset('images/marketing/cashbook.jpg') }}" data-glightbox="title: {{ __('landing.steps.release.alt') }}">
                                    <img src="{{ asset('images/marketing/cashbook.jpg') }}" alt="{{ __('landing.steps.release.alt') }}" width="1568" height="689" loading="lazy" />
                                </a>
                            </div>
                        </figure>
                    </li>
                    <li class="step">
                        <div class="step__text">
                            <span class="step__num">4.0</span>
                            <h3 class="step__heading">{{ __('landing.steps.close.heading') }}</h3>
                            <p class="step__body">{{ __('landing.steps.close.body') }}</p>
                        </div>
                        <figure class="step__figure">
                            <div class="step__shot">
                                <a class="glightbox" href="{{ asset('images/marketing/retirements.jpg') }}" data-glightbox="title: {{ __('landing.steps.close.alt') }}">
                                    <img src="{{ asset('images/marketing/retirements.jpg') }}" alt="{{ __('landing.steps.close.alt') }}" width="1568" height="689" loading="lazy" />
                                </a>
                            </div>
                        </figure>
                    </li>
                </ol>
            </div>
        </section>

        <section class="bento-section">
            <div class="container">
                <div class="bento-head">
                    <h2>{{ __('landing.bento.heading') }}</h2>
                    <p>{{ __('landing.bento.lede') }}</p>
                </div>

                <div class="bento">
                    <article class="cell">
                        <div class="cell__shot">
                            <a class="glightbox" href="{{ asset('images/marketing/dashboard.jpg') }}" data-glightbox="title: {{ __('landing.hero.figure_alt') }}">
                                <img src="{{ asset('images/marketing/dashboard.jpg') }}" alt="{{ __('landing.hero.figure_alt') }}" width="1568" height="689" loading="lazy" />
                            </a>
                        </div>
                        <div class="cell__body">
                            <h3 class="cell__title">{{ __('landing.bento.dashboard.title') }}</h3>
                            <p class="cell__text">{{ __('landing.bento.dashboard.text') }}</p>
                        </div>
                    </article>

                    <article class="cell">
                        <div class="cell__shot">
                            <a class="glightbox" href="{{ asset('images/marketing/workflow-stages.jpg') }}" data-glightbox="title: {{ __('landing.steps.route.alt') }}">
                                <img src="{{ asset('images/marketing/workflow-stages.jpg') }}" alt="{{ __('landing.steps.route.alt') }}" width="1568" height="689" loading="lazy" />
                            </a>
                        </div>
                        <div class="cell__body">
                            <h3 class="cell__title">{{ __('landing.bento.workflow_stages.title') }}</h3>
                            <p class="cell__text">{{ __('landing.bento.workflow_stages.text') }}</p>
                        </div>
                    </article>

                    <article class="cell">
                        <div class="cell__shot">
                            <a class="glightbox" href="{{ asset('images/marketing/payment-requests.jpg') }}" data-glightbox="title: {{ __('landing.steps.submit.alt') }}">
                                <img src="{{ asset('images/marketing/payment-requests.jpg') }}" alt="{{ __('landing.steps.submit.alt') }}" width="1568" height="689" loading="lazy" />
                            </a>
                        </div>
                        <div class="cell__body">
                            <h3 class="cell__title">{{ __('landing.bento.payment_requests.title') }}</h3>
                            <p class="cell__text">{{ __('landing.bento.payment_requests.text') }}</p>
                        </div>
                    </article>

                    <article class="cell">
                        <div class="cell__shot">
                            <a class="glightbox" href="{{ asset('images/marketing/cashbook.jpg') }}" data-glightbox="title: {{ __('landing.steps.release.alt') }}">
                                <img src="{{ asset('images/marketing/cashbook.jpg') }}" alt="{{ __('landing.steps.release.alt') }}" width="1568" height="689" loading="lazy" />
                            </a>
                        </div>
                        <div class="cell__body">
                            <h3 class="cell__title">{{ __('landing.bento.cashbook.title') }}</h3>
                            <p class="cell__text">{{ __('landing.bento.cashbook.text') }}</p>
                        </div>
                    </article>

                    <article class="cell">
                        <div class="cell__shot">
                            <a class="glightbox" href="{{ asset('images/marketing/retirements.jpg') }}" data-glightbox="title: {{ __('landing.steps.close.alt') }}">
                                <img src="{{ asset('images/marketing/retirements.jpg') }}" alt="{{ __('landing.steps.close.alt') }}" width="1568" height="689" loading="lazy" />
                            </a>
                        </div>
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
                <div class="spec-head">
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

        <section class="cta-strip-section">
            <div class="container cta-strip__row">
                <div class="cta-strip__text">
                    <h2>{{ __('landing.cta_strip.heading') }}</h2>
                    <p>{{ __('landing.cta_strip.lede') }}</p>
                </div>
                <a class="btn btn--accent" href="{{ route('landlord.register') }}">{{ __('landing.cta_strip.get_started') }}</a>
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
</div>
</body>
</html>
