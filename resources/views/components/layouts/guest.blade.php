<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='7' fill='%23111827'/%3E%3Cpath d='M16 5 7.5 9.25v6c0 4.9 3.36 9.18 8.5 10.375 5.14-1.195 8.5-5.475 8.5-10.375v-6L16 5Z' fill='none' stroke='%23ffffff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3Cpath d='m12.5 15.5 2.75 2.75L19.5 13' fill='none' stroke='%23ffffff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-white font-sans">
    <div class="flex min-h-full">
        <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-navy-950 p-10 lg:flex xl:w-[55%]">
            <div class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full bg-navy-500/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-40 -left-24 h-96 w-96 rounded-full bg-navy-500/20 blur-3xl"></div>

            <div class="relative flex items-center gap-3">
                <a href="{{ url('/') }}" class="relative flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-navy-900 text-white ring-1 ring-yellow-500">
                        <x-icon name="shield" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-yellow-500">{{ config('app.name') }}</p>
                        <p class="text-[10px] uppercase tracking-[0.18em] text-white">Financial control platform</p>
                    </div>
                </a>
            </div>

            <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-yellow-500/20 bg-yellow-50 px-3 py-1.5 text-xs font-semibold text-yellow-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-yellow-500"></span>
                    Revenue assurance
                </div>
                <h1 class="mt-7 max-w-2xl text-2xl font-semibold leading-[1.08] tracking-tight text-white sm:text-3xl lg:text-6xl">
                    Every collection accounted for,
                    <span class="text-yellow-600">from the cash desk to the bank.</span>
                </h1>
                <p class="mt-4 text-sm leading-relaxed text-white">
                    Capture payments across all channels, verify daily collections, control bank lodgements and
                    reconcile treasury confirmations — with segregation of duties and a complete audit trail.
                </p>

                <dl class="mt-8 grid grid-cols-3 gap-4 border-t border-white/10 pt-6">
                    <div>
                        <dt class="text-[10px] uppercase tracking-wider text-yellow-500">Channels</dt>
                        <dd class="mt-1 text-lg font-semibold text-white">5</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] uppercase tracking-wider text-yellow-500">Controls</dt>
                        <dd class="mt-1 text-lg font-semibold text-white">Dual</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] uppercase tracking-wider text-yellow-500">Audit</dt>
                        <dd class="mt-1 text-lg font-semibold text-white">Immutable</dd>
                    </div>
                </dl>
            </div>

            <p class="relative text-[11px] text-yellow-500">
                Authorised personnel only. All access and financial actions are logged.
            </p>
        </div>

        <div class="flex w-full flex-col justify-center px-5 py-10 sm:px-10 lg:w-1/2 xl:w-[45%]">
            <div class="mx-auto w-full max-w-sm">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-navy-900 ring-1 ring-yellow-500 text-white">
                        <x-icon name="shield" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-navy-950">{{ config('app.name') }}</p>
                        <p class="text-[10px] uppercase tracking-[0.18em] text-slate-500">Financial control</p>
                    </div>
                </div>

                {{ $slot }}

                <p class="mt-10 text-center text-[11px] leading-relaxed text-slate-400">
                    Protected by role-based access control.<br>
                    {{ \App\Support\Setting::string('organisation.name', 'Revenue Collection Office') }}
                </p>
            </div>
        </div>
    </div>

    <x-ui.toasts />
    @livewireScripts
</body>
</html>
