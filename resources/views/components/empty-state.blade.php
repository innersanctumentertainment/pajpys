@props([
    'title' => 'Nothing here yet',
    'description' => '',
    'icon' => '📭',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center py-16 px-6']) }}>
    <div class="text-5xl mb-4" aria-hidden="true">{{ $icon }}</div>
    <h3 class="font-display text-xl font-semibold text-pearl mb-2">{{ $title }}</h3>
    @if($description)
        <p class="text-pearl/60 text-sm max-w-sm mb-6">{{ $description }}</p>
    @endif
    @if(isset($action))
        <div>{{ $action }}</div>
    @endif
    {{ $slot }}
</div>
