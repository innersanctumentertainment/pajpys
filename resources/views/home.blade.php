@extends('layouts.app')

@section('title', 'PAJPYS')
@section('meta_description', 'PAJPYS — Hire verified Caribbean virtual assistants. Escrow-protected payments, vetted talent, and timezone-aligned support.')

@section('content')
<div class="gradient-hero min-h-screen">
    {{-- Navigation --}}
    <header class="sticky top-0 z-40 backdrop-blur-md bg-ocean-deep/70 border-b border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:h-20">
                <a href="{{ url('/') }}" class="font-display text-2xl font-bold text-pearl tracking-tight">
                    PA<span class="text-teal">JPYS</span>
                </a>

                <nav class="hidden md:flex items-center gap-8" aria-label="Main navigation">
                    <a href="#how-it-works" class="nav-link">How it works</a>
                    <a href="{{ route('services.browse') }}" class="nav-link">Browse services</a>
                    <a href="#categories" class="nav-link">Categories</a>
                    <a href="#featured" class="nav-link">Top talent</a>
                    <a href="#pricing" class="nav-link">Pricing</a>
                    <a href="#faq" class="nav-link">FAQ</a>
                </nav>

                <div class="hidden md:flex items-center gap-3">
                    @if (Route::has('login'))
                        @auth
                            <x-button variant="secondary" href="{{ url('/dashboard') }}">Dashboard</x-button>
                        @else
                            <a href="{{ route('login') }}" class="nav-link px-3 py-2">Log in</a>
                            @if (Route::has('register'))
                                <x-button href="{{ route('register') }}">Get started</x-button>
                            @else
                                <x-button href="#how-it-works">Get started</x-button>
                            @endif
                        @endauth
                    @else
                        <x-button href="#how-it-works">Get started</x-button>
                    @endif
                </div>

                <button id="mobile-nav-toggle" class="md:hidden btn-secondary !p-2" aria-label="Open menu" aria-expanded="false">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div id="mobile-nav-menu" class="hidden md:hidden border-t border-white/5 bg-ocean-deep/95 backdrop-blur-lg">
            <nav class="max-w-7xl mx-auto px-4 py-4 flex flex-col gap-3" aria-label="Mobile navigation">
                <a href="#how-it-works" class="nav-link py-2">How it works</a>
                <a href="#categories" class="nav-link py-2">Categories</a>
                <a href="#featured" class="nav-link py-2">Top VAs</a>
                <a href="#pricing" class="nav-link py-2">Pricing</a>
                <a href="#faq" class="nav-link py-2">FAQ</a>
                <div class="pt-3 border-t border-white/10 flex flex-col gap-2">
                    <x-button href="#how-it-works" class="w-full justify-center">Get started free</x-button>
                </div>
            </nav>
        </div>
    </header>

    {{-- Hero -- PLACEHOLDER TRUNCATED FOR TOOL --}}
</div>
@endsection
