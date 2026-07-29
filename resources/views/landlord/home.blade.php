<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <title>{{ config('app.name') }} — Approval workflows finance teams can prove</title>
    <meta name="description" content="Flow Ledger routes disbursement, payment, and retirement requests through configurable approval workflows, with a full audit trail, multi-tenant roles, and SSO built in." />
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
                <a class="nav-pill__link" href="#workflow">How it works</a>
                <a class="nav-pill__link" href="#security">Security</a>
            </nav>

            <div class="nav-pill__actions">
                <a class="btn btn--text" href="{{ route('landlord.login') }}">Sign in</a>
                <a class="btn btn--accent" href="{{ route('landlord.login') }}">Get started</a>
            </div>

            <button
                class="nav-pill__toggle"
                type="button"
                aria-label="Toggle menu"
                :aria-expanded="navOpen.toString()"
                @click="navOpen = !navOpen"
            ><span></span></button>
        </div>

        <div class="nav-pill__sheet" x-show="navOpen" x-cloak x-transition.duration.150ms>
            <a class="nav-pill__link" href="#workflow" @click="navOpen = false">How it works</a>
            <a class="nav-pill__link" href="#security" @click="navOpen = false">Security</a>
            <div class="nav-pill__actions">
                <a class="btn btn--outline" href="{{ route('landlord.login') }}">Sign in</a>
                <a class="btn btn--accent" href="{{ route('landlord.login') }}">Get started</a>
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container hero-split">
                <div>
                    <h1 class="hero__headline">Every approval, accounted for.</h1>
                    <p class="hero__lede">
                        Flow Ledger routes disbursement, payment, and retirement requests through the approval chains
                        your organization already runs — branch by branch, department by department, with a record
                        of every decision.
                    </p>
                    <div class="hero__ctas">
                        <a class="btn btn--accent" href="{{ route('landlord.login') }}">Sign in</a>
                        <a class="btn btn--outline" href="#workflow">See how it works</a>
                    </div>
                    <p class="hero__note">New to Flow Ledger? Ask your administrator for access to your workspace.</p>
                </div>

                <figure class="hero__figure">
                    <div class="hero__shot">
                        <a class="glightbox" href="{{ asset('images/marketing/dashboard.jpg') }}" data-glightbox="title: Flow Ledger dashboard">
                            <img
                                src="{{ asset('images/marketing/dashboard.jpg') }}"
                                alt="Flow Ledger dashboard showing a branch cash balance against its threshold"
                                width="1568"
                                height="689"
                                fetchpriority="high"
                            />
                        </a>
                    </div>
                    <figcaption>A live workspace — branch balances, thresholds, and requests in one view.</figcaption>
                </figure>
            </div>
        </section>

        <section class="steps-section" id="workflow">
            <div class="container">
                <div class="steps-head">
                    <h2>How a request moves through Flow Ledger.</h2>
                    <p>Four stages, the same ones your finance team already follows — just with a system tracking every handoff.</p>
                </div>

                <ol class="steps">
                    <li class="step">
                        <div class="step__text">
                            <span class="step__num">1.0</span>
                            <h3 class="step__heading">Submit the request</h3>
                            <p class="step__body">Staff raise payment, disbursement, or retirement requests tagged to a cost code, department, and branch — so every request starts with the context finance needs to review it.</p>
                        </div>
                        <figure class="step__figure">
                            <div class="step__shot">
                                <a class="glightbox" href="{{ asset('images/marketing/payment-requests.jpg') }}" data-glightbox="title: Payment requests">
                                    <img src="{{ asset('images/marketing/payment-requests.jpg') }}" alt="Payment requests list showing amounts, branches, and statuses" width="1568" height="689" loading="lazy" />
                                </a>
                            </div>
                        </figure>
                    </li>
                    <li class="step">
                        <div class="step__text">
                            <span class="step__num">2.0</span>
                            <h3 class="step__heading">Route it for approval</h3>
                            <p class="step__body">Multi-stage and parallel approval groups move the request through the right people, with per-stage thresholds and approver scopes. Edit a workflow later, and requests already in flight keep running on the version they started on — nothing breaks mid-approval.</p>
                        </div>
                        <figure class="step__figure">
                            <div class="step__shot">
                                <a class="glightbox" href="{{ asset('images/marketing/workflow-stages.jpg') }}" data-glightbox="title: Approval workflow configuration">
                                    <img src="{{ asset('images/marketing/workflow-stages.jpg') }}" alt="Approval workflow configuration showing sequential stages and a parallel final-approval group" width="1568" height="689" loading="lazy" />
                                </a>
                            </div>
                        </figure>
                    </li>
                    <li class="step">
                        <div class="step__text">
                            <span class="step__num">3.0</span>
                            <h3 class="step__heading">Release the funds</h3>
                            <p class="step__body">Approved disbursements post to the branch cashbook. Cash counts reconcile what's on hand against what's been recorded, branch by branch.</p>
                        </div>
                        <figure class="step__figure">
                            <div class="step__shot">
                                <a class="glightbox" href="{{ asset('images/marketing/cashbook.jpg') }}" data-glightbox="title: Cashbook">
                                    <img src="{{ asset('images/marketing/cashbook.jpg') }}" alt="Cashbook view showing cash balances across four branches" width="1568" height="689" loading="lazy" />
                                </a>
                            </div>
                        </figure>
                    </li>
                    <li class="step">
                        <div class="step__text">
                            <span class="step__num">4.0</span>
                            <h3 class="step__heading">Close the loop</h3>
                            <p class="step__body">Retirement requests tie spending back to the original disbursement — with the difference and any refund due tracked alongside a full activity log of who approved what, and when.</p>
                        </div>
                        <figure class="step__figure">
                            <div class="step__shot">
                                <a class="glightbox" href="{{ asset('images/marketing/retirements.jpg') }}" data-glightbox="title: Retirement requests">
                                    <img src="{{ asset('images/marketing/retirements.jpg') }}" alt="Retirement requests list showing amount expended and the difference against each advance" width="1568" height="689" loading="lazy" />
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
                    <h2>See Flow Ledger at work.</h2>
                    <p>Five screens from a live workspace — the same views your finance team would open today.</p>
                </div>

                <div class="bento">
                    <article class="cell">
                        <div class="cell__shot">
                            <a class="glightbox" href="{{ asset('images/marketing/dashboard.jpg') }}" data-glightbox="title: Flow Ledger dashboard">
                                <img src="{{ asset('images/marketing/dashboard.jpg') }}" alt="Flow Ledger dashboard showing a branch cash balance against its threshold" width="1568" height="689" loading="lazy" />
                            </a>
                        </div>
                        <div class="cell__body">
                            <h3 class="cell__title">Every branch, one screen</h3>
                            <p class="cell__text">Cash balances, thresholds, and requests — the full picture the moment you sign in.</p>
                        </div>
                    </article>

                    <article class="cell">
                        <div class="cell__shot">
                            <a class="glightbox" href="{{ asset('images/marketing/workflow-stages.jpg') }}" data-glightbox="title: Approval workflow configuration">
                                <img src="{{ asset('images/marketing/workflow-stages.jpg') }}" alt="Approval workflow configuration showing sequential stages and a parallel final-approval group" width="1568" height="689" loading="lazy" />
                            </a>
                        </div>
                        <div class="cell__body">
                            <h3 class="cell__title">Chains you already run</h3>
                            <p class="cell__text">Sequential and parallel approval stages, configured to match how your team signs off.</p>
                        </div>
                    </article>

                    <article class="cell">
                        <div class="cell__shot">
                            <a class="glightbox" href="{{ asset('images/marketing/payment-requests.jpg') }}" data-glightbox="title: Payment requests">
                                <img src="{{ asset('images/marketing/payment-requests.jpg') }}" alt="Payment requests list showing amounts, branches, and statuses" width="1568" height="689" loading="lazy" />
                            </a>
                        </div>
                        <div class="cell__body">
                            <h3 class="cell__title">Every request, tracked</h3>
                            <p class="cell__text">Amounts, branches, and status for every payment and disbursement request.</p>
                        </div>
                    </article>

                    <article class="cell">
                        <div class="cell__shot">
                            <a class="glightbox" href="{{ asset('images/marketing/cashbook.jpg') }}" data-glightbox="title: Cashbook">
                                <img src="{{ asset('images/marketing/cashbook.jpg') }}" alt="Cashbook view showing cash balances across four branches" width="1568" height="689" loading="lazy" />
                            </a>
                        </div>
                        <div class="cell__body">
                            <h3 class="cell__title">Reconciled, branch by branch</h3>
                            <p class="cell__text">Cash counts against what's on hand, across every branch in one ledger.</p>
                        </div>
                    </article>

                    <article class="cell">
                        <div class="cell__shot">
                            <a class="glightbox" href="{{ asset('images/marketing/retirements.jpg') }}" data-glightbox="title: Retirement requests">
                                <img src="{{ asset('images/marketing/retirements.jpg') }}" alt="Retirement requests list showing amount expended and the difference against each advance" width="1568" height="689" loading="lazy" />
                            </a>
                        </div>
                        <div class="cell__body">
                            <h3 class="cell__title">The loop, closed</h3>
                            <p class="cell__text">Retirements tie spending back to the original disbursement, with the difference tracked automatically.</p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="spec-section" id="security">
            <div class="container">
                <div class="spec-head">
                    <h2>Built for organizations, not just users.</h2>
                    <p>Every tenant is its own workspace, with its own people, permissions, and record of who did what.</p>
                </div>

                <table class="spec-sheet">
                    <tbody>
                        <tr>
                            <th scope="row">Multi-tenant workspaces</th>
                            <td>Every organization gets an isolated workspace — its own branches, departments, roles, and requests.</td>
                        </tr>
                        <tr>
                            <th scope="row">Roles &amp; permissions</th>
                            <td>Granular, per-tenant roles decide who can submit, approve, or administer — down to individual permissions.</td>
                        </tr>
                        <tr>
                            <th scope="row">Single sign-on</th>
                            <td>Sign in through your identity provider. Logout stays in sync across sessions via backchannel logout.</td>
                        </tr>
                        <tr>
                            <th scope="row">Activity log</th>
                            <td>Every request, approval, disbursement, and retirement is recorded and attributable.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="cta-strip-section">
            <div class="container cta-strip__row">
                <div class="cta-strip__text">
                    <h2>Ready to route your first approval?</h2>
                    <p>Sign in to your Flow Ledger workspace.</p>
                </div>
                <a class="btn btn--accent" href="{{ route('landlord.login') }}">Sign in</a>
            </div>
        </section>
    </main>

    <footer class="foot-line">
        <div class="container foot-line__inner">
            <span>{{ config('app.name') }}</span>
            <span class="foot-line__sep">·</span>
            <span>Approval workflows for finance teams</span>
            <span class="foot-line__sep">·</span>
            <span>&copy; {{ now()->year }} {{ config('app.name') }}</span>
        </div>
    </footer>
</div>
</body>
</html>
