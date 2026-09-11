<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0a1628">

    <title>@yield('title', 'Dashboard') — PAJPYS</title>

    <link rel="manifest" href="/manifest.json">
    @include('partials.fonts')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="antialiased bg-ocean-deep min-h-screen">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="dashboard-sidebar hidden lg:flex lg:flex-col w-64 shrink-0">
            <div class="p-6 border-b border-white/10">
                <a href="{{ url('/') }}" class="font-display text-xl font-bold text-pearl tracking-tight">
                    Assist<span class="text-teal">Carib</span>
                </a>
            </div>

            <nav class="flex-1 p-4 space-y-1" aria-label="Dashboard navigation">
                @hasSection('sidebar')
                    @yield('sidebar')
                @else
                    @include('partials.dashboard-sidebar')
                @endif
            </nav>

            <div class="p-4 border-t border-white/10">
                <x-role-switcher />
            </div>
        </aside>

        {{-- Main area --}}
        <div class="flex-1 flex flex-col min-w-0">
            {{-- Top bar --}}
            <header class="glass-card rounded-none border-x-0 border-t-0 px-4 lg:px-8 py-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <button id="mobile-nav-toggle" class="lg:hidden btn-secondary !p-2" aria-label="Toggle sidebar" aria-expanded="false">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h1 class="font-display text-lg font-semibold text-pearl">@yield('page_title', 'Dashboard')</h1>
                </div>

                <div class="flex items-center gap-3">
                    {{-- Notifications --}}
                    <a href="{{ route('notifications.index') }}" class="relative p-2 rounded-lg text-pearl/70 hover:text-pearl hover:bg-white/5 transition-colors focus-visible:outline-none" aria-label="Notifications">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-coral rounded-full" aria-hidden="true"></span>
                    </a>

                    @auth
                        <span class="hidden sm:block text-sm text-pearl/60">{{ auth()->user()->name ?? 'User' }}</span>
                    @endauth
                </div>
            </header>

            {{-- Mobile sidebar overlay --}}
            <div id="mobile-nav-menu" class="hidden lg:hidden fixed inset-0 z-50">
                <div class="absolute inset-0 bg-ocean-deep/80 backdrop-blur-sm" onclick="document.getElementById('mobile-nav-menu').classList.add('hidden')"></div>
                <aside class="dashboard-sidebar relative w-64 h-full p-4">
                    <nav class="space-y-1" aria-label="Mobile dashboard navigation">
                        @hasSection('sidebar')
                            @yield('sidebar')
                        @else
                            @include('partials.dashboard-sidebar')
                        @endif
                    </nav>
                    <div class="mt-6 pt-4 border-t border-white/10">
                        <x-role-switcher />
                    </div>
                </aside>
            </div>

            <main class="flex-1 p-4 lg:p-8 overflow-auto">
                @if(session('success'))
                    <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
                @endif
                @if(session('error'))
                    <x-alert type="error" class="mb-6">{{ session('error') }}</x-alert>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
