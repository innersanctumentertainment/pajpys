@props([
    'type' => 'info',
])

@php
    $styles = match($type) {
        'success' => 'border-teal/40 bg-teal/10 text-teal',
        'error' => 'border-coral/40 bg-coral/10 text-coral-light',
        'warning' => 'border-coral-light/40 bg-coral/10 text-coral-light',
        default => 'border-white/20 bg-white/5 text-pearl/80',
    };
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-3 p-4 rounded-xl border {$styles}", 'role' => 'alert']) }}>
    @if($type === 'success')
        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    @elseif($type === 'error')
        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    @else
        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    @endif
    <div class="text-sm leading-relaxed">{{ $slot }}</div>
</div>
