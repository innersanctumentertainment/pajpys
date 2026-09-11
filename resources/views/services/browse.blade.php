@extends('layouts.app')

@section('title', 'Browse Services — PAJPYS')
@section('meta_description', 'Discover professional services on PAJPYS. Post your services or hire talent across the Caribbean and beyond.')

@section('content')
<div class="gradient-hero min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <header class="mb-10">
            <x-badge class="mb-4">Post Your Services</x-badge>
            <h1 class="font-display text-4xl font-bold text-pearl mb-3">Browse services</h1>
            <p class="text-pearl/70 max-w-2xl">Find professionals offering virtual assistance, creative work, technical help, and more.</p>
        </header>

        <form method="GET" class="glass-card p-4 mb-8 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label for="q" class="form-label">Search</label>
                <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-input w-full" placeholder="What do you need?">
            </div>
            <div>
                <label for="category" class="form-label">Category</label>
                <select id="category" name="category" class="form-input">
                    <option value="">All categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->slug }}" @selected(($filters['category'] ?? '') === $cat->slug)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 text-pearl/80 text-sm pb-2">
                <input type="checkbox" name="va_only" value="1" @checked($filters['va_only'] ?? false)>
                Virtual assistants only
            </label>
            <x-button type="submit">Filter</x-button>
        </form>

        @if ($services->isEmpty())
            <x-empty-state title="No services found" description="Try adjusting your filters or post your own service on PAJPYS." />
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($services as $service)
                    <a href="{{ route('services.show', $service) }}" class="glass-card p-6 block hover:border-teal/40 transition-colors group">
                        @if ($service->is_va_service)
                            <x-badge class="mb-3">Virtual Assistant</x-badge>
                        @endif
                        <h2 class="font-display text-lg font-semibold text-pearl group-hover:text-teal transition-colors">{{ $service->title }}</h2>
                        <p class="text-pearl/60 text-sm mt-2 line-clamp-3">{{ Str::limit($service->description, 120) }}</p>
                        @if ($service->price_amount_minor)
                            <p class="mt-4 text-teal font-semibold">
                                From {{ $service->currency }} {{ number_format($service->price_amount_minor / 100, 2) }}
                            </p>
                        @endif
                    </a>
                @endforeach
            </div>
            <div class="mt-8">{{ $services->links() }}</div>
        @endif
    </div>
</div>
@endsection
