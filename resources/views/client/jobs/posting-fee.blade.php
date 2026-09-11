@extends('layouts.dashboard')

@section('title', 'Job Posting Fee')
@section('page_title', 'Job Posting Fee')

@section('sidebar')
    @include('partials.dashboard-sidebar', ['active' => 'client.jobs'])
@endsection

@section('content')
    <article class="glass-card p-8 max-w-2xl">
        <h2 class="font-display text-2xl font-semibold text-pearl mb-2">Post your job on PAJPYS</h2>
        <p class="text-pearl/70 mb-6">
            A one-time posting fee is required before your job goes live for providers and virtual assistants to discover.
        </p>

        <dl class="space-y-3 mb-8">
            <div class="flex justify-between border-b border-white/10 pb-2">
                <dt class="text-pearl/70">Job</dt>
                <dd class="text-pearl font-medium">{{ $job->title }}</dd>
            </div>
            <div class="flex justify-between border-b border-white/10 pb-2">
                <dt class="text-pearl/70">Posting fee</dt>
                <dd class="text-teal font-display text-xl font-bold">{{ $feeCurrency }} ${{ number_format($feeAmount, 2) }}</dd>
            </div>
        </dl>

        <p class="text-sm text-pearl/60 mb-6">
            After this fee is paid, you will fund the job budget separately. Withdrawals from your wallet are subject to a 15% platform fee.
        </p>

        <form method="POST" action="{{ route('client.jobs.posting-fee.initiate', $job) }}" id="posting-fee-form">
            @csrf
            <input type="hidden" name="idempotency_key" value="posting-fee-{{ $job->id }}-{{ uniqid() }}">
            <x-button type="submit" class="w-full justify-center">Pay {{ $feeCurrency }} ${{ number_format($feeAmount, 2) }} &amp; Continue</x-button>
        </form>

        <a href="{{ route('client.jobs.show', $job) }}" class="block text-center mt-4 text-pearl/60 hover:text-pearl text-sm">Back to job</a>
    </article>
@endsection
