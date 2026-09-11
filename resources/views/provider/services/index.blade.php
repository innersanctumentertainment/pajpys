@extends('layouts.dashboard')

@section('title', 'My Services')
@section('page_title', 'My Services')

@section('sidebar')
    @include('partials.dashboard-sidebar', ['active' => 'provider.services'])
@endsection

@section('content')
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="font-display text-2xl font-semibold text-pearl">Your service listings</h2>
            <p class="text-pearl/60 text-sm mt-1">Post your services and reach clients on PAJPYS.</p>
        </div>
        <x-button href="{{ route('provider.services.create') }}">Post a service</x-button>
    </div>

    @if ($listings->isEmpty())
        <x-empty-state title="No services yet" description="Create your first service listing and start getting inquiries." />
        <div class="mt-6 text-center">
            <x-button href="{{ route('provider.services.create') }}">Post your first service</x-button>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($listings as $listing)
                <a href="{{ route('provider.services.show', $listing) }}" class="glass-card p-5 flex justify-between items-center block hover:border-teal/30">
                    <div>
                        <h3 class="font-semibold text-pearl">{{ $listing->title }}</h3>
                        <p class="text-sm text-pearl/60">{{ ucfirst($listing->status->value) }}</p>
                    </div>
                    <x-badge>{{ $listing->is_va_service ? 'VA Service' : 'Service' }}</x-badge>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $listings->links() }}</div>
    @endif
@endsection
