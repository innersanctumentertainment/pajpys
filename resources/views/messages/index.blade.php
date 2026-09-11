@extends('layouts.dashboard')

@section('title', 'Messages — '.$job->title)
@section('page_title', 'Messages')

@section('sidebar')
    @include('partials.dashboard-sidebar')
@endsection

@section('content')
    <header class="mb-6">
        <p class="text-pearl/60 text-sm">
            Conversation for
            <a href="{{ auth()->user()->isClient() ? route('client.jobs.show', $job) : route('va.jobs.show', $job) }}" class="text-teal hover:underline">
                {{ $job->title }}
            </a>
        </p>
    </header>

    <section aria-label="Message thread" class="glass-card p-4 mb-6 max-h-[32rem] overflow-y-auto flex flex-col gap-4">
        @forelse ($messages as $msg)
            @php $isOwn = $msg->sender_id === auth()->id(); @endphp
            <article @class([
                'max-w-[85%] rounded-lg p-4',
                'bg-teal/15 ml-auto' => $isOwn,
                'bg-white/5 mr-auto' => ! $isOwn,
            ])>
                <header class="flex items-center gap-2 mb-1">
                    <span class="text-sm font-medium text-pearl">{{ $msg->sender->name ?? 'User' }}</span>
                    <time datetime="{{ $msg->created_at->toIso8601String() }}" class="text-xs text-pearl/40">
                        {{ $msg->created_at->format('M j, g:i A') }}
                    </time>
                </header>
                <p class="text-sm text-pearl/85 whitespace-pre-wrap">{{ $msg->body }}</p>
            </article>
        @empty
            <x-empty-state
                title="No messages yet"
                description="Start the conversation below."
                icon="💬"
            />
        @endforelse
    </section>

    @if ($messages->hasPages())
        <nav class="mb-6" aria-label="Messages pagination">
            {{ $messages->links() }}
        </nav>
    @endif

    <section aria-labelledby="compose-heading">
        <x-card>
            <x-slot:header>
                <h2 id="compose-heading" class="font-display text-lg font-semibold text-pearl">Send a message</h2>
            </x-slot:header>

            <form method="POST" action="{{ route('jobs.messages.store', $job) }}" class="space-y-4">
                @csrf

                <div>
                    <label for="body" class="form-label">Message</label>
                    <textarea id="body" name="body" required maxlength="10000" class="form-textarea" placeholder="Type your message..." rows="4">{{ old('body') }}</textarea>
                    @error('body')<p class="mt-1 text-sm text-coral">{{ $message }}</p>@enderror
                </div>

                <p class="text-xs text-pearl/50">Sharing contact information outside the platform is monitored and may violate policy.</p>

                <button type="submit" class="btn-primary">Send message</button>
            </form>
        </x-card>
    </section>
@endsection
