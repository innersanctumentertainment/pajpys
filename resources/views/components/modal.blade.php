@props([
    'name' => 'modal',
    'title' => '',
])

<div
    id="modal-{{ $name }}"
    class="fixed inset-0 z-50 hidden items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    @if($title) aria-labelledby="modal-title-{{ $name }}" @endif
    {{ $attributes }}
>
    <div class="absolute inset-0 bg-ocean-deep/80 backdrop-blur-sm" data-modal-close="{{ $name }}"></div>

    <div class="glass-card relative w-full max-w-lg p-6 z-10">
        @if($title)
            <h2 id="modal-title-{{ $name }}" class="font-display text-xl font-semibold text-pearl mb-4">{{ $title }}</h2>
        @endif

        {{ $slot }}

        <button
            type="button"
            class="absolute top-4 right-4 p-1 text-pearl/50 hover:text-pearl rounded-lg focus-visible:outline-none"
            data-modal-close="{{ $name }}"
            aria-label="Close modal"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>
