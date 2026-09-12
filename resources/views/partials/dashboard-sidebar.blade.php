@php
    $active = $active ?? '';
@endphp

<a href="{{ route('dashboard') }}" @class(['dashboard-nav-link', 'active' => $active === 'dashboard'])>
    Overview
</a>

@if (auth()->user()->isProvider())
    <p class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wider text-pearl/40">Provider</p>
    <a href="{{ route('provider.services.index') }}" @class(['dashboard-nav-link', 'active' => $active === 'provider.services'])>
        My Services
    </a>
    <a href="{{ route('provider.services.create') }}" @class(['dashboard-nav-link', 'active' => $active === 'provider.services.create'])>
        Post a Service
    </a>
@endif

@if (auth()->user()->isClient())
    <p class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wider text-pearl/40">Post a Job</p>
    <a href="{{ route('client.jobs.index') }}" @class(['dashboard-nav-link', 'active' => $active === 'client.jobs'])>
        My Jobs
    </a>
    <a href="{{ route('client.templates.index') }}" @class(['dashboard-nav-link', 'active' => $active === 'client.templates'])>
        Job Templates
    </a>
@endif

@if (auth()->user()->isVa())
    <p class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wider text-pearl/40">Jobs &amp; Work</p>
    <a href="{{ route('va.jobs.discover') }}" @class(['dashboard-nav-link', 'active' => $active === 'va.jobs.discover'])>
        Discover Jobs
    </a>
    <a href="{{ route('va.work.index') }}" @class(['dashboard-nav-link', 'active' => $active === 'va.work'])>
        My Work
    </a>
    <a href="{{ route('va.profile.show') }}" @class(['dashboard-nav-link', 'active' => $active === 'va.profile'])>
        Profile
    </a>
    <a href="{{ route('va.verification.index') }}" @class(['dashboard-nav-link', 'active' => $active === 'va.verification'])>
        Verification
    </a>
@endif

<p class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wider text-pearl/40">Account</p>
<a href="{{ route('wallet.index') }}" @class(['dashboard-nav-link', 'active' => $active === 'wallet'])>
    Wallet
</a>
<a href="{{ route('notifications.index') }}" @class(['dashboard-nav-link', 'active' => $active === 'notifications'])>
    Notifications
</a>

@if (auth()->user()->hasAnyRole(['master_admin', 'staff']))
    <p class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wider text-pearl/40">Administration</p>
    <a href="{{ route('admin.dashboard') }}" @class(['dashboard-nav-link', 'active' => $active === 'admin.dashboard'])>
        Admin Dashboard
    </a>
@endif

@unless (auth()->user()->hasTwoFactorEnabled())
    <a href="{{ route('two-factor.setup') }}" @class(['dashboard-nav-link', 'active' => $active === 'security'])>
        Security
    </a>
@endunless

<form method="POST" action="{{ route('logout') }}" class="mt-4">
    @csrf
    <button type="submit" class="dashboard-nav-link w-full text-left">Log out</button>
</form>
