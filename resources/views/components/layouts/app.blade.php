@php
    $authUser = auth()->user();
    $navItems = $authUser?->isAdmin()
        ? [
            ['route' => 'admin.users', 'match' => 'admin.*', 'label' => 'Pengguna', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
            ['route' => 'profile', 'match' => 'profile', 'label' => 'Profil', 'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z'],
        ]
        : [
            ['route' => 'stocks.index', 'match' => 'stocks.*', 'label' => 'Stok Barang', 'icon' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z'],
            ['route' => 'profile', 'match' => 'profile', 'label' => 'Profil', 'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z'],
        ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Aplikasi inventaris stok barang: catat barang, cari, dan atur jumlah stok dengan mudah.">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='7' fill='%230f766e'/%3E%3Cpath d='M8 12h16v11H8zM7 9h18v3H7z' fill='white'/%3E%3C/svg%3E">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    @livewireStyles

    {{-- Kelas warna status (dipakai oleh enum StockStatus): bg-green-100 text-green-800 bg-yellow-100 text-yellow-800 bg-red-100 text-red-800 --}}
    <style>
        :root {
            --pine: #0e2b29;
            --pine-soft: #17403c;
            --teal: #0f766e;
            --teal-dark: #115e59;
            --page: #f2f5f4;
            --line: #dce5e2;
            --ink: #1b2a28;
            --muted: #5f7370;
        }
        [x-cloak] { display: none !important; }
        body { background: var(--page); color: var(--ink); font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
        .tabular { font-variant-numeric: tabular-nums; }

        .page-title { font-size: 1.5rem; font-weight: 700; letter-spacing: -0.01em; }
        .page-sub { color: var(--muted); font-size: .875rem; margin-top: .15rem; }
        .panel { background: #fff; border: 1px solid var(--line); border-radius: .75rem; }

        .btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; border-radius: .5rem; padding: .5rem .9rem; font-size: .875rem; font-weight: 600; line-height: 1.25rem; border: 1px solid transparent; cursor: pointer; transition: background-color .15s, border-color .15s; white-space: nowrap; }
        .btn:focus-visible { outline: 2px solid var(--teal); outline-offset: 2px; }
        .btn:disabled { opacity: .6; cursor: not-allowed; }
        .btn-primary { background: var(--teal); color: #fff; }
        .btn-primary:hover { background: var(--teal-dark); }
        .btn-ghost { background: #fff; border-color: #cfdad7; color: var(--ink); }
        .btn-ghost:hover { background: var(--page); }
        .btn-danger { background: #fff; border-color: #f1c4c0; color: #b42318; }
        .btn-danger:hover { background: #fef3f2; }
        .btn-sm { padding: .3rem .6rem; font-size: .8125rem; }

        .field-input { width: 100%; border-radius: .5rem; border: 1px solid #cbd8d4; background: #fff; padding: .5rem .75rem; font-size: .875rem; color: var(--ink); }
        .field-input:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(15, 118, 110, .18); }
        .field-label { display: block; margin-bottom: .25rem; font-size: .8125rem; font-weight: 600; color: #34504c; }
        .field-error { margin-top: .25rem; font-size: .8125rem; color: #b42318; }

        .data-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .data-table th { text-align: left; font-size: .75rem; font-weight: 600; color: var(--muted); padding: .65rem 1rem; background: #f7faf9; border-bottom: 1px solid var(--line); }
        .data-table td { padding: .75rem 1rem; border-bottom: 1px solid #edf2f0; vertical-align: middle; }
        .data-table tbody tr:hover { background: #fafcfb; }
        .data-table tbody tr:last-child td { border-bottom: 0; }

        .stepper { display: inline-flex; align-items: center; border: 1px solid #cbd8d4; border-radius: .5rem; background: #fff; overflow: hidden; }
        .stepper button { width: 1.9rem; height: 1.9rem; font-size: 1rem; line-height: 1; color: var(--teal-dark); }
        .stepper button:hover { background: #e8f3f1; }
        .stepper button:disabled { color: #9db3af; background: transparent; cursor: not-allowed; }
        .stepper span { min-width: 3.4rem; text-align: center; font-size: .875rem; font-weight: 600; padding: 0 .35rem; }

        .md { font-size: .9375rem; line-height: 1.7; max-width: 70ch; }
        .md h1, .md h2, .md h3 { font-weight: 700; margin: 1.1em 0 .4em; line-height: 1.3; }
        .md h1 { font-size: 1.25rem; } .md h2 { font-size: 1.125rem; } .md h3 { font-size: 1rem; }
        .md p { margin: .6em 0; }
        .md ul { list-style: disc; padding-left: 1.4rem; margin: .6em 0; }
        .md ol { list-style: decimal; padding-left: 1.4rem; margin: .6em 0; }
        .md code { background: #eef3f2; padding: .1rem .35rem; border-radius: .3rem; font-size: .85em; }
        .md a { color: var(--teal); text-decoration: underline; }
        .md blockquote { border-left: 3px solid var(--line); padding-left: .9rem; color: var(--muted); }

        @media (prefers-reduced-motion: reduce) { * { transition: none !important; animation: none !important; } }
    </style>
</head>
<body class="antialiased">
@auth
    <div x-data="{ open: false }" class="min-h-screen lg:flex">
        <header class="flex items-center justify-between px-4 py-3 text-white lg:hidden" style="background: var(--pine)">
            <span class="text-base font-bold">{{ config('app.name') }}</span>
            <button type="button" class="rounded-md p-1.5 hover:bg-white/10" x-on:click="open = true" aria-label="Buka menu">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
            </button>
        </header>

        <div x-show="open" x-cloak class="fixed inset-0 z-30 bg-black/50 lg:hidden" x-on:click="open = false"></div>

        <aside class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col text-slate-200 transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
               style="background: var(--pine)"
               x-bind:class="open ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex items-center gap-2.5 px-5 py-5">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg" style="background: var(--teal)">
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                </span>
                <span class="text-lg font-bold text-white">{{ config('app.name') }}</span>
            </div>

            <nav class="flex-1 space-y-1 px-3 py-2" aria-label="Menu utama">
                @foreach ($navItems as $item)
                    @php($active = request()->routeIs($item['match']))
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ $active ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                       @if($active) aria-current="page" @endif>
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-white/10 p-4">
                <div class="flex items-center gap-3">
                    @if ($authUser->profile_photo_path)
                        <img src="{{ asset('storage/'.$authUser->profile_photo_path) }}" alt="" class="h-10 w-10 rounded-full object-cover">
                    @else
                        <span class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold text-white" style="background: var(--pine-soft)">{{ $authUser->initials() }}</span>
                    @endif
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-white">{{ $authUser->name }}</p>
                        <p class="truncate text-xs text-slate-400">{{ $authUser->isAdmin() ? 'Admin' : 'Member' }} · {{ $authUser->username }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg border border-white/15 px-3 py-2 text-sm font-medium text-slate-200 hover:bg-white/10">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <main class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">
            <div class="mx-auto max-w-6xl">
                {{ $slot }}
            </div>
        </main>
    </div>
@else
    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            <div class="mb-6 flex items-center justify-center gap-2.5">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl" style="background: var(--pine)">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                </span>
                <span class="text-xl font-bold">{{ config('app.name') }}</span>
            </div>
            <main>
                {{ $slot }}
            </main>
        </div>
    </div>
@endauth

<div x-data="{ show: {{ session('status') ? 'true' : 'false' }}, message: @js(session('status') ?? ''), timer: null }"
     x-init="if (show) { timer = setTimeout(() => show = false, 3500) }"
     x-on:notify.window="message = $event.detail.message; show = true; clearTimeout(timer); timer = setTimeout(() => show = false, 3500)"
     x-show="show" x-cloak x-transition.opacity
     class="fixed bottom-5 right-5 z-[60] max-w-xs rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg"
     style="background: var(--pine)" role="status">
    <span x-text="message"></span>
</div>

@livewireScripts
</body>
</html>
