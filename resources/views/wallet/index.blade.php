@extends('layouts.dashboard')

@section('title', 'Wallet')
@section('page_title', 'Wallet')

@section('sidebar')
    @include('partials.dashboard-sidebar', ['active' => 'wallet'])
@endsection

@section('content')
    <section aria-labelledby="balance-heading" class="mb-10">
        <article class="glass-card p-8 text-center">
            <h2 id="balance-heading" class="text-sm text-pearl/50 uppercase tracking-wide mb-2">Available balance</h2>
            <p class="font-display text-4xl font-bold text-pearl mb-1">
                {{ $currency }} {{ number_format($balance, 2) }}
            </p>
            <p class="text-sm text-pearl/40">{{ number_format($balanceMinor) }} minor units</p>
        </article>
    </section>

    @if (auth()->user()->isVa())
        <section aria-labelledby="withdraw-heading">
            <x-card>
                <x-slot:header>
                    <h2 id="withdraw-heading" class="font-display text-lg font-semibold text-pearl">Request withdrawal</h2>
                </x-slot:header>

                <form method="POST" action="{{ route('wallet.withdrawals.request') }}" class="space-y-5">
                    @csrf

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label for="amount" class="form-label">Amount</label>
                            <input type="number" id="amount" name="amount" value="{{ old('amount') }}" required min="0" step="0.01" class="form-input" inputmode="decimal">
                            @error('amount')<p class="mt-1 text-sm text-coral">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="currency" class="form-label">Currency</label>
                            <input type="text" id="currency" name="currency" value="{{ old('currency', $currency) }}" maxlength="3" class="form-input">
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="form-label">Notes <span class="text-pearl/50">(optional)</span></label>
                        <textarea id="notes" name="notes" maxlength="1000" class="form-textarea !min-h-[4rem]">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="btn-primary">Submit withdrawal request</button>
                </form>
            </x-card>
        </section>
    @else
        <p class="text-pearl/60 text-sm">Client wallet funds are used to pay for jobs on the platform.</p>
    @endif
@endsection
