@extends('layouts.dashboard')

@section('title', $listing->title)
@section('page_title', 'Service Details')

@section('sidebar')
    @include('partials.dashboard-sidebar', ['active' => 'provider.services'])
@endsection

@section('content')
    <article class="glass-card p-8 max-w-3xl">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="font-display text-2xl font-semibold text-pearl">{{ $listing->title }}</h2>
                <x-badge class="mt-2">{{ ucfirst($listing->status->value) }}</x-badge>
            </div>
            @if ($listing->status->value === 'draft')
                <form method="POST" action="{{ route('provider.services.publish', $listing) }}">
                    @csrf
                    <x-button type="submit">Publish service</x-button>
                </form>
            @elseif ($listing->published_at)
                <x-button variant="secondary" href="{{ route('services.show', $listing) }}">View public page</x-button>
            @endif
        </div>
        <p class="text-pearl/80 whitespace-pre-line">{{ $listing->description }}</p>
        @if ($listing->price_amount_minor)
            <p class="mt-6 text-teal font-semibold text-lg">{{ $listing->currency }} {{ number_format($listing->price_amount_minor / 100, 2) }}</p>
        @endif
    </article>
@endsection
