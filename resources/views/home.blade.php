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
            </div>
        </div>
    </header>
    <section id="how-it-works" class="py-20 lg:py-28 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="section-heading text-pearl mb-4">How PAJPYS works</h2>
        </div>
    </section>
    <section id="categories" class="py-20 lg:py-28 bg-ocean-mid/30"></section>
    <section id="featured" class="py-20 lg:py-28"></section>
    <section id="pricing" class="py-20 lg:py-28 border-t border-white/5 bg-ocean-mid/30"></section>
    <section id="faq" class="py-20 lg:py-28"></section>
    <footer class="border-t border-white/5 bg-ocean-deep/80 py-12 lg:py-16">
        <div class="max-w-7xl mx-auto px-4 text-xs text-pearl/40">&copy; {{ date('Y') }} PAJPYS</div>
    </footer>
</div>
@endsection
