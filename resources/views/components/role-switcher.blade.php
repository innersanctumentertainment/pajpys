@props([
    'current' => null,
])

@php
    $current = $current ?? ($activeRole ?? 'client');
@endphp

@if (isset($availableRoles) && count($availableRoles) > 1)
    <form method="POST" action="{{ route('role.switch') }}" id="role-switcher-form">
        @csrf
        <label for="role-switcher" class="sr-only">Switch role</label>
        <select
            id="role-switcher"
            name="role"
            class="w-full px-3 py-2 text-sm font-medium text-pearl bg-white/5 border border-white/10 rounded-lg appearance-none cursor-pointer focus-visible:outline-none hover:bg-white/8 transition-colors"
            aria-label="Switch between client and VA roles"
            onchange="this.form.submit()"
        >
            @if (in_array('client', $availableRoles, true))
                <option value="client" @selected($current === 'client')>Client view</option>
            @endif
            @if (in_array('va', $availableRoles, true))
                <option value="va" @selected($current === 'va')>VA view</option>
            @endif
        </select>
    </form>
@endif
