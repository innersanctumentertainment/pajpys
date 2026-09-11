@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Platform Overview')
@section('page_subtitle', 'Real-time marketplace statistics')

@section('sidebar')
    <a href="{{ route('admin.dashboard') }}" class="dashboard-nav-link active">Overview</a>
    <a href="{{ route('admin.users.index') }}" class="dashboard-nav-link">Users</a>
    <a href="{{ route('admin.verifications.index') }}" class="dashboard-nav-link">Verifications</a>
    <a href="{{ route('admin.payouts.index') }}" class="dashboard-nav-link">Payouts</a>
    <a href="{{ route('admin.disputes.index') }}" class="dashboard-nav-link">Disputes</a>
    <a href="{{ route('admin.settings.index') }}" class="dashboard-nav-link">Settings</a>
    <a href="{{ route('dashboard') }}" class="dashboard-nav-link mt-4">← Main dashboard</a>
@endsection

@section('content')
    <section aria-labelledby="users-stats-heading" class="mb-10">
        <h2 id="users-stats-heading" class="sr-only">User statistics</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <article class="glass-card p-6">
                <p class="text-sm text-pearl/50 mb-1">Total users</p>
                <p class="font-display text-3xl font-bold text-pearl">{{ number_format($stats['users']['total']) }}</p>
            </article>
            <article class="glass-card p-6">
                <p class="text-sm text-pearl/50 mb-1">Clients</p>
                <p class="font-display text-3xl font-bold text-teal">{{ number_format($stats['users']['clients']) }}</p>
            </article>
            <article class="glass-card p-6">
                <p class="text-sm text-pearl/50 mb-1">Virtual assistants</p>
                <p class="font-display text-3xl font-bold text-teal">{{ number_format($stats['users']['vas']) }}</p>
            </article>
        </div>
    </section>

    <section aria-labelledby="jobs-stats-heading" class="mb-10">
        <h2 id="jobs-stats-heading" class="font-display text-lg font-semibold text-pearl mb-4">Jobs</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4">
            @foreach ([
                'total' => ['label' => 'Total', 'variant' => 'default'],
                'open' => ['label' => 'Open', 'variant' => 'teal'],
                'in_progress' => ['label' => 'In progress', 'variant' => 'default'],
                'completed' => ['label' => 'Completed', 'variant' => 'verified'],
                'disputed' => ['label' => 'Disputed', 'variant' => 'coral'],
            ] as $key => $meta)
                <article class="glass-card p-5">
                    <p class="text-xs text-pearl/50 mb-1 uppercase tracking-wide">{{ $meta['label'] }}</p>
                    <p @class([
                        'font-display text-2xl font-bold',
                        'text-pearl' => $meta['variant'] === 'default',
                        'text-teal' => $meta['variant'] === 'teal',
                        'text-coral' => $meta['variant'] === 'coral',
                    ])>{{ number_format($stats['jobs'][$key]) }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="payments-stats-heading" class="mb-10">
        <h2 id="payments-stats-heading" class="font-display text-lg font-semibold text-pearl mb-4">Payments</h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <article class="glass-card p-6">
                <p class="text-sm text-pearl/50 mb-1">Total transactions</p>
                <p class="font-display text-3xl font-bold text-pearl">{{ number_format($stats['payments']['total']) }}</p>
            </article>
            <article class="glass-card p-6">
                <p class="text-sm text-pearl/50 mb-1">Succeeded volume</p>
                <p class="font-display text-3xl font-bold text-teal">
                    TTD {{ number_format($stats['payments']['volume_minor'] / 100, 2) }}
                </p>
            </article>
        </div>
    </section>

    <section aria-labelledby="pending-stats-heading">
        <h2 id="pending-stats-heading" class="font-display text-lg font-semibold text-pearl mb-4">Pending actions</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('admin.verifications.index') }}" class="glass-card p-5 hover:border-teal/30 transition-colors block">
                <p class="text-xs text-pearl/50 mb-1 uppercase tracking-wide">Verifications</p>
                <p class="font-display text-2xl font-bold text-coral">{{ number_format($stats['pending']['verifications']) }}</p>
            </a>
            <a href="{{ route('admin.payouts.index') }}" class="glass-card p-5 hover:border-teal/30 transition-colors block">
                <p class="text-xs text-pearl/50 mb-1 uppercase tracking-wide">Withdrawals</p>
                <p class="font-display text-2xl font-bold text-coral">{{ number_format($stats['pending']['withdrawals']) }}</p>
            </a>
            <a href="{{ route('admin.disputes.index') }}" class="glass-card p-5 hover:border-teal/30 transition-colors block">
                <p class="text-xs text-pearl/50 mb-1 uppercase tracking-wide">Disputes</p>
                <p class="font-display text-2xl font-bold text-coral">{{ number_format($stats['pending']['disputes']) }}</p>
            </a>
            <article class="glass-card p-5">
                <p class="text-xs text-pearl/50 mb-1 uppercase tracking-wide">Reports</p>
                <p class="font-display text-2xl font-bold text-coral">{{ number_format($stats['pending']['reports']) }}</p>
            </article>
        </div>
    </section>
@endsection
