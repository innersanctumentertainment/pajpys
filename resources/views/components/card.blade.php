@props([
    'variant' => 'glass',
])

@php
    $classes = match($variant) {
        'light' => 'glass-card-light',
        'glass' => 'glass-card',
        default => 'glass-card',
    };
@endphp

<div {{ $attributes->merge(['class' => $classes . ' p-6']) }}>
    @if(isset($header))
        <div class="mb-4 pb-4 border-b border-white/10">
            {{ $header }}
        </div>
    @endif

    {{ $slot }}

    @if(isset($footer))
        <div class="mt-4 pt-4 border-t border-white/10">
            {{ $footer }}
        </div>
    @endif
</div>
