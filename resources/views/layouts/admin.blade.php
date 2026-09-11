<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#060e1a">

    <title>@yield('title', 'Admin') — PAJPYS Command Center</title>

    <link rel="manifest" href="/manifest.json">
    @include('partials.fonts')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="antialiased bg-[#060e1a] min-h-screen">
    <div class="flex min-h-screen">
        {{-- Admin sidebar --}}
        <aside class="admin-sidebar hidden lg:flex lg:flex-col w-72 shrink-0">
            <div class="p-6 border-b border-coral/20">
                <a href="{{ url('/') }}" class="font-display text-xl font-bold text-pearl tracking-tight">
                    Assist<span class="text-coral">Carib</span>
                </a>
                <p class="text-xs text-pearl/40 mt-1 uppercase tracking-widest">Command Center</p>
            </div>

            <nav class="flex-1 p-4 space-y-1" aria-label="Admin navigation">
                @yield('sidebar')
            </nav>

            <div class="p-4 border-t border-coral/20 text-xs text-pearl/40">
                Admin · {{ now()->format('M j, Y') }}
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="px-4 lg:px-8 py-4 flex items-center justify-between gap-4 border-b border-white/5 bg-ocean-deep/50 backdrop-blur-md">
                <div class="flex items-center gap-4">
                    <button id="mobile-nav-toggle" class="lg:hidden btn-secondary !p-2" aria-label="Toggle admin menu" aria-expanded="false">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div>
                        <h1 class="font-display text-lg font-semibold text-pearl">@yield('page_title', 'Overview')</h1>
                        <p class="text-xs text-pearl/40">@yield('page_subtitle', 'Platform administration')</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="badge badge-coral">Live</span>
                    <button class="relative p-2 rounded-lg text-pearl/70 hover:text-pearl hover:bg-white/5 transition-colors" aria-label="Admin notifications">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </button>
                </div>
            </header>

            <main class="flex-1 p-4 lg:p-8 overflow-auto">
                @if(session('success'))
                    <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
