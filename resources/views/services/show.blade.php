@extends('layouts.app')

@section('title', $service->title . ' — PAJPYS')

@section('content')
<div class="gradient-hero min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <a href="{{ route('services.browse') }}" class="text-teal text-sm mb-6 inline-block">&larr; All services</a>

        <article class="glass-card p-8">
            <div class="flex flex-wrap gap-2 mb-4">
                @if ($service->is_va_service)
                    <x-badge>Virtual Assistant</x-badge>
                @endif
                @if ($service->category)
                    <x-badge>{{ $service->category->name }}</x-badge>
                @endif
            </div>

            <h1 class="font-display text-3xl font-bold text-pearl mb-4">{{ $service->title }}</h1>
            <p class="text-pearl/80 leading-relaxed mb-6">{{ $service->description }}</p>

            @if ($service->deliverables)
                <section class="mb-6">
                    <h2 class="font-semibold text-pearl mb-2">What you get</h2>
                    <p class="text-pearl/70 whitespace-pre-line">{{ $service->deliverables }}</p>
                </section>
            @endif

            @if ($service->price_amount_minor)
                <p class="text-2xl font-display text-teal font-bold mb-6">
                    {{ $service->currency }} {{ number_format($service->price_amount_minor / 100, 2) }}
                    @if ($service->pricing_type === 'hourly')<span class="text-base text-pearl/60">/ hour</span>@endif
                </p>
            @endif

            @auth
                @if (auth()->user()->hasVerifiedEmail())
                    <form method="POST" action="{{ route('services.inquire', $service) }}" class="border-t border-white/10 pt-6 mt-6">
                        @csrf
                        <label for="message" class="form-label">Send an inquiry</label>
                        <textarea id="message" name="message" rows="4" required minlength="20" class="form-input w-full mb-4" placeholder="Describe what you need..."></textarea>
                        <x-button type="submit">Send inquiry</x-button>
                    </form>
                @endif
            @else
                <p class="text-pearl/60"><a href="{{ route('login') }}" class="text-teal">Log in</a> to contact this provider.</p>
            @endauth
        </article>
    </div>
</div>
@endsection
