@props([
    'variant' => 'default',
])

@php
    $classes = match($variant) {
        'coral' => 'badge badge-coral',
        'verified' => 'badge badge-verified',
        default => 'badge',
    };
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
