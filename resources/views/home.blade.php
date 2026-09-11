@extends('layouts.app')

@section('title', 'PAJPYS')
@section('meta_description', 'PAJPYS — Hire verified Caribbean virtual assistants. Escrow-protected payments, vetted talent, and timezone-aligned support.')

@section('content')
<div class="gradient-hero min-h-screen">
    <header class="sticky top-0 z-40 backdrop-blur-md bg-ocean-deep/70 border-b border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:h-20">
                <a href="{{ url('/') }}" class="font-display text-2xl font-bold text-pearl tracking-tight">
                    PA<span class="text-teal">JPYS</span>
                </a>
            </div>
        </div>
    </header>
    <section class="relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-24">
            <h1 class="font-display text-4xl font-bold text-pearl">Post a job. Post your services.</h1>
        </div>
    </section>
</div>
@endsection
