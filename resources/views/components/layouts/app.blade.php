<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-slate-100 font-sans" x-data="{ sidebarOpen: false }">
    <div class="min-h-full lg:flex">
        <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-navy-950/60 lg:hidden" @click="sidebarOpen = false"></div>

        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-[248px] flex-col bg-navy-950 transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:self-start lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-16 shrink-0 items-center gap-2.5 border-b border-white/10 px-5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-navy-500/15 ring-1 ring-navy-500/30">
                    <x-icon name="shield" class="h-4.5 w-4.5 text-white" />
                </div>
                <div class="min-w-0">
                    <p class="truncate text-[13px] font-semibold leading-tight text-white">{{ \App\Support\Setting::string('organisation.short_name', 'Revenue') }}</p>
                    <p class="truncate text-[10px] font-medium uppercase tracking-[0.14em] text-slate-400">Collection control</p>
                </div>
                <button type="button" class="ml-auto rounded-md p-1 text-slate-400 hover:bg-white/5 hover:text-white lg:hidden" @click="sidebarOpen = false" aria-label="Close navigation">
                    <x-icon name="x-mark" class="h-4 w-4" />
                </button>
            </div>

            <nav class="scrollbar-thin min-h-0 flex-1 space-y-5 overflow-y-auto overscroll-contain px-3 py-4">
                @foreach (\App\Support\Navigation::groups(auth()->user()) as $group)
                    <div>
                        <p class="px-3 pb-1.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $group['label'] }}</p>
                        <div class="space-y-0.5">
                            @foreach ($group['items'] as $item)
                                <a href="{{ route($item['route']) }}" class="sidebar-link {{ \App\Support\Navigation::isActive($item) ? 'sidebar-link-active' : '' }}">
                                    <x-icon :name="$item['icon']" class="h-4 w-4 shrink-0" />
                                    <span class="truncate">{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>

            <div class="shrink-0 border-t border-white/10 p-3">
                <div class="flex items-center gap-2.5 rounded-lg bg-white/5 p-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-navy-800 text-[11px] font-semibold text-white ring-1 ring-white/20">
                        {{ auth()->user()->initials() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-semibold text-white">{{ auth()->user()->name }}</p>
                        <p class="truncate text-[10px] text-slate-400">{{ auth()->user()->primaryRole() ?? 'No role' }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="app-header sticky top-0 z-20 flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur lg:px-6">
                <button type="button" class="rounded-md p-2 text-slate-500 hover:bg-slate-100 lg:hidden" @click="sidebarOpen = true" aria-label="Open navigation">
                    <x-icon name="menu" class="h-5 w-5" />
                </button>

                <div class="min-w-0">
                    <p class="truncate text-[11px] font-medium uppercase tracking-wider text-slate-400">{{ now()->format('l, j F Y') }}</p>
                    <p class="truncate text-sm font-semibold text-slate-800">{{ auth()->user()->branch?->name ?? 'Group-wide access' }}</p>
                </div>

                <div class="ml-auto flex items-center gap-1.5">
                    @if (Route::has('notifications.index'))
                        <a href="{{ route('notifications.index') }}" class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-navy-800" aria-label="Notifications">
                            <x-icon name="bell" class="h-5 w-5" />
                            @php($unread = auth()->user()->unreadNotifications()->count())
                            @if ($unread > 0)
                                <span class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-bold text-white">
                                    {{ $unread > 99 ? '99+' : $unread }}
                                </span>
                            @endif
                        </a>
                    @endif

                    <div class="mx-1 hidden h-6 w-px bg-slate-200 sm:block"></div>

                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button" class="flex items-center gap-2 rounded-lg p-1.5 pr-2 transition hover:bg-slate-100" @click="open = ! open">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-navy-800 text-[11px] font-semibold text-white">{{ auth()->user()->initials() }}</span>
                            <span class="hidden text-left sm:block">
                                <span class="block max-w-[140px] truncate text-xs font-semibold text-slate-800">{{ auth()->user()->name }}</span>
                                <span class="block text-[10px] text-slate-500">{{ auth()->user()->primaryRole() ?? 'User' }}</span>
                            </span>
                            <x-icon name="chevron-down" class="h-3.5 w-3.5 text-slate-400" />
                        </button>

                        <div x-cloak x-show="open" x-transition.origin.top.right class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                            <div class="border-b border-slate-100 px-4 py-3">
                                <p class="text-xs font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                                <p class="truncate text-[11px] text-slate-500">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="p-1.5">
                                <a href="{{ route('profile') }}" class="flex items-center gap-2 rounded-md px-2.5 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">
                                    <x-icon name="user-circle" class="h-4 w-4" /> My profile
                                </a>
                                @if (Route::has('notifications.index'))
                                    <a href="{{ route('notifications.index') }}" class="flex items-center gap-2 rounded-md px-2.5 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">
                                        <x-icon name="bell" class="h-4 w-4" /> Notifications
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left text-xs font-medium text-rose-600 hover:bg-rose-50">
                                        <x-icon name="logout" class="h-4 w-4" /> Sign out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 lg:px-7 lg:py-8">
                {{ $slot }}
            </main>

            <footer class="no-print border-t border-slate-200 px-4 py-4 text-[11px] text-slate-400 lg:px-7">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span>{{ config('app.name') }} · {{ \App\Support\Setting::string('organisation.name', 'Revenue Collection Office') }}</span>
                    <span>All financial actions are audited. {{ now()->format('H:i') }} UTC</span>
                </div>
            </footer>
        </div>
    </div>

    <x-ui.toasts />
    @livewireScripts
</body>
</html>
