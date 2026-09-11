@extends('layouts.dashboard')

@section('title', 'Notifications')
@section('page_title', 'Notifications')

@section('sidebar')
    @include('partials.dashboard-sidebar', ['active' => 'notifications'])
@endsection

@section('content')
    @if (auth()->user()->unreadNotifications()->exists())
        <form method="POST" action="{{ route('notifications.read-all') }}" class="mb-6">
            @csrf
            <button type="submit" class="btn-secondary">Mark all as read</button>
        </form>
    @endif

    @if ($notifications->isEmpty())
        <x-empty-state
            title="No notifications"
            description="You're all caught up. New alerts will appear here."
            icon="🔔"
        />
    @else
        <ul class="space-y-3" role="list" aria-label="Notifications">
            @foreach ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $title = $data['title'] ?? 'Notification';
                    $body = $data['body'] ?? '';
                    $isUnread = $notification->read_at === null;
                @endphp
                <li>
                    <article @class(['glass-card p-5', 'border-teal/30' => $isUnread])>
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h2 class="font-medium text-pearl">{{ $title }}</h2>
                                    @if ($isUnread)
                                        <x-badge variant="coral">New</x-badge>
                                    @endif
                                </div>
                                <p class="text-sm text-pearl/70 mb-2">{{ $body }}</p>
                                <time datetime="{{ $notification->created_at->toIso8601String() }}" class="text-xs text-pearl/40">
                                    {{ $notification->created_at->diffForHumans() }}
                                </time>
                            </div>
                            @if ($isUnread)
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    <button type="submit" class="btn-secondary !py-2 !px-3 text-sm shrink-0">Mark read</button>
                                </form>
                            @endif
                        </div>
                    </article>
                </li>
            @endforeach
        </ul>

        @if ($notifications->hasPages())
            <nav class="mt-8" aria-label="Notifications pagination">
                {{ $notifications->links() }}
            </nav>
        @endif
    @endif
@endsection
