<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-slate-100 font-sans">
    <div class="flex min-h-full">
        <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-navy-950 p-10 lg:flex xl:w-[55%]">
            <div class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full bg-mint-500/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-40 -left-24 h-96 w-96 rounded-full bg-navy-500/20 blur-3xl"></div>

            <div class="relative flex items-center gap-3">
                <a href="{{ url('/') }}" class="relative flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-mint-500/15 ring-1 ring-mint-500/30">
                        <x-icon name="shield" class="h-5 w-5 text-mint-400" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">{{ config('app.name') }}</p>
                        <p class="text-[10px] uppercase tracking-[0.18em] text-slate-400">Financial control platform</p>
                    </div>
                </a>
            </div>

            <div class="relative max-w-lg">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-mint-400">Revenue assurance</p>
                <h1 class="mt-4 text-3xl font-semibold leading-tight tracking-tight text-white">
                    Every collection accounted for, from the cash desk to the bank.
                </h1>
                <p class="mt-4 text-sm leading-relaxed text-slate-300">
                    Capture payments across all channels, verify daily collections, control bank lodgements and
                    reconcile treasury confirmations — with segregation of duties and a complete audit trail.
                </p>

                <dl class="mt-8 grid grid-cols-3 gap-4 border-t border-white/10 pt-6">
                    <div>
                        <dt class="text-[10px] uppercase tracking-wider text-slate-500">Channels</dt>
                        <dd class="mt-1 text-lg font-semibold text-white">5</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] uppercase tracking-wider text-slate-500">Controls</dt>
                        <dd class="mt-1 text-lg font-semibold text-white">Dual</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] uppercase tracking-wider text-slate-500">Audit</dt>
                        <dd class="mt-1 text-lg font-semibold text-white">Immutable</dd>
                    </div>
                </dl>
            </div>

            <p class="relative text-[11px] text-slate-500">
                Authorised personnel only. All access and financial actions are logged.
            </p>
        </div>

        <div class="flex w-full flex-col justify-center px-5 py-10 sm:px-10 lg:w-1/2 xl:w-[45%]">
            <div class="mx-auto w-full max-w-sm">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-navy-900">
                        <x-icon name="shield" class="h-5 w-5 text-mint-400" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ config('app.name') }}</p>
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
