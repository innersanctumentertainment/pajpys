@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('sidebar')
    @include('partials.dashboard-sidebar', ['active' => 'dashboard'])
@endsection

@section('content')
    <section aria-labelledby="welcome-heading">
        <h2 id="welcome-heading" class="font-display text-2xl font-semibold text-pearl mb-2">
            Welcome, {{ $user->name }}
        </h2>
        <p class="text-pearl/70 mb-6">{{ $user->email }}</p>

        @if (session('status'))
            <p role="status" class="text-teal mb-4">{{ session('status') }}</p>
        @endif

        @unless ($user->hasVerifiedEmail())
            <article class="glass-card p-6 mb-6">
                <h3 class="font-semibold text-pearl mb-2">Verify your email</h3>
                <p class="text-pearl/70 mb-4">You must verify your email before accessing the marketplace.</p>
                <a href="{{ route('verification.notice') }}" class="btn-primary">Verify email</a>
            </article>
        @endunless

        @if ($user->isClient() && $user->hasVerifiedEmail())
            <article class="glass-card p-6 mb-6">
                <h3 class="font-semibold text-pearl mb-2">Client workspace</h3>
                <p class="text-pearl/70 mb-4">Post jobs, manage candidates, and fund projects.</p>
                <a href="{{ route('client.jobs.index') }}" class="btn-primary">My Jobs</a>
            </article>
        @endif

        @if ($user->isVa() && $user->hasVerifiedEmail())
            <article class="glass-card p-6 mb-6">
                <h3 class="font-semibold text-pearl mb-2">VA workspace</h3>
                <p class="text-pearl/70 mb-4">Discover opportunities and manage your active work.</p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('va.jobs.discover') }}" class="btn-primary">Discover Jobs</a>
                    <a href="{{ route('va.work.index') }}" class="btn-secondary">My Work</a>
                </div>
            </article>
        @endif

        @if ($user->isVa() && ! $user->isVaApproved())
            <article class="glass-card p-6 mb-6">
                <h3 class="font-semibold text-pearl mb-2">VA profile pending approval</h3>
                <p class="text-pearl/70">Your virtual assistant profile is awaiting staff review.</p>
            </article>
        @endif

        @if ($user->hasAnyRole(['master_admin', 'staff']))
            <article class="glass-card p-6 mb-6">
                <h3 class="font-semibold text-pearl mb-2">Administration</h3>
                <p class="text-pearl/70 mb-4">Monitor platform activity and manage users.</p>
                <a href="{{ route('admin.dashboard') }}" class="btn-primary">Admin Dashboard</a>
            </article>
        @endif

        @isset($activeRole)
            <p class="text-pearl/60">Active role: <strong class="text-pearl">{{ ucfirst($activeRole) }}</strong></p>
        @endisset
    </section>
@endsection
