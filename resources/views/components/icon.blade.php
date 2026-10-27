@props(['name' => 'circle', 'class' => 'h-5 w-5'])

<svg {{ $attributes->merge(['class' => $class]) }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
    @switch($name)
        @case('dashboard')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75h6v6h-6v-6Zm10.5 0h6v10.5h-6V3.75ZM3.75 14.25h6v6h-6v-6Zm10.5 3h6v3h-6v-3Z" />
            @break
        @case('banknotes')
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.5A2.25 2.25 0 0 1 4.5 5.25h15a2.25 2.25 0 0 1 2.25 2.25v9A2.25 2.25 0 0 1 19.5 18.75h-15A2.25 2.25 0 0 1 2.25 16.5v-9Z" />
            <circle cx="12" cy="12" r="2.5" />
            <path stroke-linecap="round" d="M5.25 9.75h.008v.008H5.25V9.75Zm13.5 4.5h.008v.008h-.008V14.25Z" />
            @break
        @case('receipt')
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 3.75h12v16.5l-2.25-1.5-2.25 1.5-2.25-1.5L9 20.25 6.75 18.75 6 20.25V3.75Z" />
            <path stroke-linecap="round" d="M9 8.25h6M9 11.25h6M9 14.25h3.75" />
            @break
        @case('users')
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.5a6 6 0 0 0-12 0M9 11.25a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5ZM16.5 4.86a3.75 3.75 0 0 1 0 6.78M18.75 19.5a5.9 5.9 0 0 0-2.25-4.5" />
            @break
        @case('bank')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9.75 12 4.5l9 5.25M4.5 9.75v8.25m4.5-8.25v8.25m6-8.25v8.25m4.5-8.25v8.25M3 20.25h18M2.25 18h19.5" />
            @break
        @case('shield')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3 4.5 6v5.25c0 4.35 3 8.25 7.5 9.75 4.5-1.5 7.5-5.4 7.5-9.75V6L12 3Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 11.25 1.5 1.5 3.25-3.25" />
            @break
        @case('chart')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 20.25h16.5M6.75 16.5V9.75m4.5 6.75V6.75m4.5 9.75v-4.5m4.5 4.5v-7.5" />
            @break
        @case('settings')
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.75h4.5l.45 2.1 1.7.98 1.95-.9 2.25 3.9-1.5 1.5v1.95l1.5 1.5-2.25 3.9-1.95-.9-1.7.98-.45 2.1h-4.5l-.45-2.1-1.7-.98-1.95.9-2.25-3.9 1.5-1.5v-1.95l-1.5-1.5 2.25-3.9 1.95.9 1.7-.98.45-2.1Z" />
            <circle cx="12" cy="12" r="3" />
            @break
        @case('plus')
            <path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15" />
            @break
        @case('search')
            <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 20.25-4.5-4.5m2.25-5.25a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" />
            @break
        @case('bell')
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 18.75a2.25 2.25 0 0 1-4.5 0m-2.25-3h9l-1.5-2.25V9a3.75 3.75 0 1 0-7.5 0v4.5l-1.5 2.25h10.5Z" />
            @break
        @case('chevron-down')
            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
            @break
        @case('chevron-right')
            <path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6" />
            @break
        @case('check')
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 4.5 4.5 10.5-10.5" />
            @break
        @case('x-mark')
            <path stroke-linecap="round" d="M6 18 18 6M6 6l12 12" />
            @break
        @case('alert')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3h.008v.008H12v-.008ZM10.34 3.79 2.7 17.04A1.5 1.5 0 0 0 4 19.29h16a1.5 1.5 0 0 0 1.3-2.25L13.66 3.79a1.5 1.5 0 0 0-2.6 0Z" />
            @break
        @case('document')
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 3.75H6.75A1.5 1.5 0 0 0 5.25 5.25v13.5a1.5 1.5 0 0 0 1.5 1.5h10.5a1.5 1.5 0 0 0 1.5-1.5V7.5l-3.75-3.75Z" />
            <path stroke-linecap="round" d="M14.25 3.75V7.5h3.75M8.25 12h7.5M8.25 15h5.25" />
            @break
        @case('upload')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V4.5m0 0L7.5 9M12 4.5 16.5 9M4.5 16.5v1.5a1.5 1.5 0 0 0 1.5 1.5h12a1.5 1.5 0 0 0 1.5-1.5v-1.5" />
            @break
        @case('download')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v12m0 0 4.5-4.5M12 16.5 7.5 12M4.5 19.5h15" />
            @break
        @case('filter')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5l-6.75 7.5v5.25l-3 1.5v-6.75l-6.75-7.5Z" />
            @break
        @case('calendar')
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3.75v3M17.25 3.75v3M3.75 8.25h16.5M4.5 5.25h15a.75.75 0 0 1 .75.75v13.5a.75.75 0 0 1-.75.75h-15a.75.75 0 0 1-.75-.75V6a.75.75 0 0 1 .75-.75Z" />
            @break
        @case('credit-card')
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.5A2.25 2.25 0 0 1 4.5 5.25h15A2.25 2.25 0 0 1 21.75 7.5v9a2.25 2.25 0 0 1-2.25 2.25h-15A2.25 2.25 0 0 1 2.25 16.5v-9ZM2.25 9.75h19.5M6 15h3.75" />
            @break
        @case('scale')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75v16.5M8.25 20.25h7.5M4.5 6.75h15M6 6.75 3 13.5h6L6 6.75Zm12 0-3 6.75h6l-3-6.75Z" />
            @break
        @case('clipboard')
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5h6M9 4.5a1.5 1.5 0 0 0-1.5 1.5v.75h9V6A1.5 1.5 0 0 0 15 4.5M9 4.5H6.75A1.5 1.5 0 0 0 5.25 6v13.5a1.5 1.5 0 0 0 1.5 1.5h10.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H15M8.25 11.25h7.5M8.25 14.25h7.5M8.25 17.25h4.5" />
            @break
        @case('lock')
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V7.5a4.5 4.5 0 1 0-9 0v3M6 10.5h12a1.5 1.5 0 0 1 1.5 1.5v7.5A1.5 1.5 0 0 1 18 21H6a1.5 1.5 0 0 1-1.5-1.5V12A1.5 1.5 0 0 1 6 10.5Z" />
            <path stroke-linecap="round" d="M12 14.25v3.75" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="8.25" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5V12l3 1.5" />
            @break
        @case('trending-up')
            <path stroke-linecap="round" stroke-linejoin="round" d="m3 16.5 5.25-5.25 3.75 3.75L21 6m0 0h-5.25M21 6v5.25" />
            @break
        @case('wallet')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5A1.5 1.5 0 0 1 5.25 6h13.5A1.5 1.5 0 0 1 20.25 7.5v9a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-9Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 11.25h3.75v3H16.5a1.5 1.5 0 1 1 0-3Z" />
            @break
        @case('pencil')
            <path stroke-linecap="round" stroke-linejoin="round" d="m16.5 4.5 3 3L8.25 18.75l-4.5 1.5 1.5-4.5L16.5 4.5Z" />
            @break
        @case('trash')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75h15M9.75 3.75h4.5l.75 3H9l.75-3ZM6.75 6.75l.75 13.5h9l.75-13.5M10.5 10.5v6M13.5 10.5v6" />
            @break
        @case('arrow-left')
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12H4.5m0 0 5.25-5.25M4.5 12l5.25 5.25" />
            @break
        @case('arrow-right')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0-5.25-5.25M19.5 12l-5.25 5.25" />
            @break
        @case('arrow-up-right')
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 16.5 16.5 7.5m0 0H9m7.5 0v7.5" />
            @break
        @case('external')
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5h6v6M19.5 4.5 10.5 13.5M9 6H6a1.5 1.5 0 0 0-1.5 1.5v10.5A1.5 1.5 0 0 0 6 19.5h10.5a1.5 1.5 0 0 0 1.5-1.5v-3" />
            @break
        @case('check-circle')
            <circle cx="12" cy="12" r="8.25" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 12 2.25 2.25 5.25-4.5" />
            @break
        @case('x-circle')
            <circle cx="12" cy="12" r="8.25" />
            <path stroke-linecap="round" d="m9 9 6 6m0-6-6 6" />
            @break
        @case('information')
            <circle cx="12" cy="12" r="8.25" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 11.25v5.25m0-8.25h.008v.008H12v-.008Z" />
            @break
        @case('user-circle')
            <circle cx="12" cy="12" r="8.25" />
            <circle cx="12" cy="10.5" r="2.25" />
            <path stroke-linecap="round" d="M6.75 18a6 6 0 0 1 10.5 0" />
            @break
        @case('dots')
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12h.008v.008H6.75V12Zm5.25 0h.008v.008H12V12Zm5.25 0h.008v.008h-.008V12Z" />
            @break
        @case('logout')
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A1.5 1.5 0 0 0 14.25 3.75h-9A1.5 1.5 0 0 0 3.75 5.25v13.5A1.5 1.5 0 0 0 5.25 20.25h9a1.5 1.5 0 0 0 1.5-1.5V15M9.75 12h11.25m0 0-3-3m3 3-3 3" />
            @break
        @case('menu')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
            @break
        @case('refresh')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0 1 12.75-5.36L20.25 9M20.25 4.5V9h-4.5M19.5 12a7.5 7.5 0 0 1-12.75 5.36L3.75 15m0 4.5V15h4.5" />
            @break
        @case('eye')
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12S5.25 5.25 12 5.25 21.75 12 21.75 12 18.75 18.75 12 18.75 2.25 12 2.25 12Z" />
            <circle cx="12" cy="12" r="2.75" />
            @break
        @case('print')
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25V3.75h9v4.5M7.5 17.25H5.25a1.5 1.5 0 0 1-1.5-1.5v-6a1.5 1.5 0 0 1 1.5-1.5h13.5a1.5 1.5 0 0 1 1.5 1.5v6a1.5 1.5 0 0 1-1.5 1.5H16.5M7.5 14.25h9v6h-9v-6Z" />
            @break
        @case('sparkles')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75 13.5 9l5.25 1.5L13.5 12 12 17.25 10.5 12 5.25 10.5 10.5 9 12 3.75ZM18.75 15.75l.75 2.25 2.25.75-2.25.75-.75 2.25-.75-2.25L15.75 18l2.25-.75.75-2.25Z" />
            @break
        @case('building-library')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M4.5 21V9l7.5-4.5L19.5 9v12M8.25 21v-5.25h7.5V21M8.25 11.25h.008v.008H8.25v-.008Zm3.75 0h.008v.008H12v-.008Zm3.75 0h.008v.008h-.008v-.008Z" />
            @break
        @case('bolt')
            <path stroke-linecap="round" stroke-linejoin="round" d="m13.5 3-7.5 10.5h6L10.5 21l7.5-10.5h-6L13.5 3Z" />
            @break
        @case('paper-airplane')
            <path stroke-linecap="round" stroke-linejoin="round" d="m3 11.25 18-7.5-7.5 18-3-7.5-7.5-3Z" />
            @break
        @case('adjustments')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6h10.5m3 0h3m-16.5 6h3m3 0h10.5m-16.5 6h10.5m3 0h3M12.75 4.5v3m-3 3v3m6 3v3" />
            @break
        @default
            <circle cx="12" cy="12" r="8.25" />
    @endswitch
</svg>
