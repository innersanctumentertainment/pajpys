@extends('layouts.dashboard')

@section('title', 'Post a Service')
@section('page_title', 'Post a Service')

@section('sidebar')
    @include('partials.dashboard-sidebar', ['active' => 'provider.services'])
@endsection

@section('content')
    <article class="glass-card p-8 max-w-3xl">
        <h2 class="font-display text-2xl font-semibold text-pearl mb-6">Post your service on PAJPYS</h2>

        <form method="POST" action="{{ route('provider.services.store') }}" class="space-y-5">
            @csrf
            <div>
                <label for="title" class="form-label">Service title</label>
                <input type="text" id="title" name="title" required class="form-input w-full" value="{{ old('title') }}">
            </div>
            <div>
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" rows="5" required class="form-input w-full">{{ old('description') }}</textarea>
            </div>
            <div>
                <label for="deliverables" class="form-label">Deliverables (optional)</label>
                <textarea id="deliverables" name="deliverables" rows="3" class="form-input w-full">{{ old('deliverables') }}</textarea>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="category_id" class="form-label">Category</label>
                    <select id="category_id" name="category_id" class="form-input w-full">
                        <option value="">Select category</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="service_type" class="form-label">Service type</label>
                    <select id="service_type" name="service_type" class="form-input w-full">
                        <option value="general">General</option>
                        <option value="virtual_assistant">Administrative</option>
                        <option value="professional">Professional</option>
                        <option value="creative">Creative</option>
                        <option value="technical">Technical</option>
                    </select>
                </div>
            </div>
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label for="pricing_type" class="form-label">Pricing</label>
                    <select id="pricing_type" name="pricing_type" class="form-input w-full">
                        <option value="fixed">Fixed price</option>
                        <option value="hourly">Hourly</option>
                        <option value="negotiable">Negotiable</option>
                    </select>
                </div>
                <div>
                    <label for="price_amount" class="form-label">Price</label>
                    <input type="number" step="0.01" id="price_amount" name="price_amount" class="form-input w-full" value="{{ old('price_amount') }}">
                </div>
                <div>
                    <x-currency-select
                        name="currency"
                        :selected="old('currency', $defaultCurrency ?? 'TTD')"
                        :allowed="$allowedCurrencies ?? ['TTD']"
                        :default="$defaultCurrency ?? 'TTD'"
                    />
                </div>
            </div>
            <label class="flex items-center gap-2 text-pearl/80">
                <input type="checkbox" name="is_va_service" value="1">
                This is an administrative / support service
            </label>
            <x-button type="submit">Save as draft</x-button>
        </form>
    </article>
@endsection
