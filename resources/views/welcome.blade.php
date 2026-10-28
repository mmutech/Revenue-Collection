<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="A secure workspace for managing revenue collection, lodgements, reconciliation, and reporting.">
    <title>{{ config('app.name', 'Revenue Collection') }}</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='7' fill='%23111827'/%3E%3Cpath d='M16 5 7.5 9.25v6c0 4.9 3.36 9.18 8.5 10.375 5.14-1.195 8.5-5.475 8.5-10.375v-6L16 5Z' fill='none' stroke='%23ffffff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3Cpath d='m12.5 15.5 2.75 2.75L19.5 13' fill='none' stroke='%23ffffff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-white font-sans text-slate-800 antialiased">
    <header class="border-b border-slate-200/80 bg-white">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8" aria-label="Main navigation">
            <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="{{ config('app.name') }} home">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-navy-950 ring-1 ring-navy-900 text-white">
                    <x-icon name="shield" class="h-5 w-5" />
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-bold text-slate-950">{{ config('app.name', 'Revenue Collection') }}</span>
                    <span class="block text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">Revenue operations</span>
                </span>
            </a>

            @auth
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-lg bg-navy-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-navy-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-navy-700">
                    Open workspace
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            @else
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-lg bg-navy-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-navy-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-navy-700">
                    Staff sign in
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            @endauth
        </nav>
    </header>

    <main>
        <section class="relative isolate overflow-hidden bg-slate-50">
            <div class="pointer-events-none absolute -right-36 -top-40 -z-10 h-[28rem] w-[28rem] rounded-full bg-yellow-500/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-48 left-1/4 -z-10 h-[28rem] w-[28rem] rounded-full bg-navy-500/10 blur-3xl"></div>

            <div class="mx-auto grid max-w-7xl items-center gap-14 px-5 py-16 sm:px-8 sm:py-20 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16 lg:py-28">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-yellow-500/20 bg-yellow-50 px-3 py-1.5 text-xs font-semibold text-yellow-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-yellow-500"></span>
                        A single workspace for revenue operations
                    </div>

                    <h1 class="mt-7 max-w-2xl text-4xl font-semibold leading-[1.08] tracking-tight text-navy-950 sm:text-5xl lg:text-6xl">
                        Every collection.
                        <span class="text-yellow-600">Accounted for.</span>
                    </h1>

                    <p class="mt-6 max-w-xl text-base leading-7 text-slate-600 sm:text-lg sm:leading-8">
                        Follow revenue from payment capture through clearing, bank lodgement, and reconciliation—with clear controls at every step.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-navy-950 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-navy-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-navy-700">
                                Go to dashboard
                                <x-icon name="arrow-right" class="h-4 w-4" />
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-navy-950 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-navy-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-navy-700">
                                Sign in to your workspace
                                <x-icon name="arrow-right" class="h-4 w-4" />
                            </a>
                        @endauth
                        <a href="#workflow" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">
                            Explore the workflow
                        </a>
                    </div>

                    <p class="mt-6 flex items-center gap-2 text-xs leading-5 text-slate-500">
                        <x-icon name="shield" class="h-4 w-4 shrink-0 text-yellow-600" />
                        Staff access is protected by role-based permissions and audited actions.
                    </p>
                </div>

                <div class="relative mx-auto w-full max-w-xl">
                    <div class="absolute -inset-4 rounded-[2rem] bg-gradient-to-br from-yellow-500/10 via-transparent to-navy-500/10 blur-xl"></div>
                    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-navy-950/10">
                        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Revenue operations</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">Collection workflow</p>
                            </div>
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-yellow-50 text-yellow-700">
                                <x-icon name="refresh" class="h-4.5 w-4.5" />
                            </span>
                        </div>

                        <ol class="space-y-3 p-5 sm:p-6">
                            <li class="flex items-center gap-4 rounded-xl border border-yellow-500/20 bg-yellow-50/70 p-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-yellow-700 shadow-sm">
                                    <x-icon name="banknotes" class="h-5 w-5" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-slate-900">Capture payments</span>
                                    <span class="mt-0.5 block text-xs text-slate-600">Record collections by channel</span>
                                </span>
                                <x-icon name="check-circle" class="h-5 w-5 shrink-0 text-yellow-600" />
                            </li>
                            <li class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-navy-700">
                                    <x-icon name="clipboard" class="h-5 w-5" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-slate-900">Verify collections</span>
                                    <span class="mt-0.5 block text-xs text-slate-600">Review and submit daily batches</span>
                                </span>
                                <span class="h-2 w-2 shrink-0 rounded-full bg-slate-300"></span>
                            </li>
                            <li class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-navy-700">
                                    <x-icon name="building-library" class="h-5 w-5" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-slate-900">Lodge and reconcile</span>
                                    <span class="mt-0.5 block text-xs text-slate-600">Track deposits and treasury confirmation</span>
                                </span>
                                <span class="h-2 w-2 shrink-0 rounded-full bg-slate-300"></span>
                            </li>
                        </ol>

                        <div class="border-t border-slate-100 bg-slate-50/80 px-5 py-4 sm:px-6">
                            <p class="flex items-center gap-2 text-xs font-medium text-slate-600">
                                <x-icon name="lock" class="h-4 w-4 text-slate-500" />
                                Role-based access · Branch-aware controls · Audit trail
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="workflow" class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20">
            <div class="max-w-2xl">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-yellow-700">One connected process</p>
                <h2 class="mt-3 text-2xl font-semibold tracking-tight text-navy-950 sm:text-3xl">Built for accountable collections</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600 sm:text-base">
                    Bring operational teams and financial controls together, with each step visible to the people responsible for it.
                </p>
            </div>

            <div class="mt-9 grid gap-4 md:grid-cols-3">
                <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-navy-50 text-navy-800">
                        <x-icon name="banknotes" class="h-5 w-5" />
                    </span>
                    <h3 class="mt-5 text-sm font-semibold text-slate-900">Capture with context</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Record payments with channel-specific details, branch attribution, and receipt controls.</p>
                </article>

                <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-mint-50 text-mint-700">
                        <x-icon name="scale" class="h-5 w-5" />
                    </span>
                    <h3 class="mt-5 text-sm font-semibold text- -900">Review with clear roles</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Manage batches, exceptions, and approvals with permissions that support separation of duties.</p>
                </article>

                <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                        <x-icon name="chart" class="h-5 w-5" />
                    </span>
                    <h3 class="mt-5 text-sm font-semibold text-slate-900">Reconcile with confidence</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Follow lodgements, treasury checks, discrepancies, and reporting in one operational system.</p>
                </article>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-slate-50">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-6 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8">
            <span>© {{ now()->format('Y') }} {{ config('app.name', 'Revenue Collection') }}</span>
            <span>Authorised personnel only. Financial actions are recorded for audit.</span>
        </div>
    </footer>
</body>
</html>
