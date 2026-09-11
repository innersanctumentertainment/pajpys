@extends('layouts.dashboard')

@section('title', 'Discover Jobs')
@section('page_title', 'Discover Jobs')

@section('sidebar')
    @include('partials.dashboard-sidebar', ['active' => 'va.jobs.discover'])
@endsection

@section('content')
    @if ($invitations->isNotEmpty())
        <section class="mb-10" aria-labelledby="invitations-heading">
            <h2 id="invitations-heading" class="font-display text-lg font-semibold text-pearl mb-4">Pending invitations</h2>
            <ul class="space-y-3" role="list">
                @foreach ($invitations as $invitation)
                    <li>
                        <article class="glass-card p-5 border-teal/20">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <h3 class="font-medium text-pearl">{{ $invitation->job->title }}</h3>
                                    <p class="text-sm text-pearl/60">Invited by {{ $invitation->client->name ?? 'Client' }}</p>
                                </div>
                                <a href="{{ route('va.jobs.show', $invitation->job) }}" class="btn-primary shrink-0">View invitation</a>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($recommended->isNotEmpty())
        <section class="mb-10" aria-labelledby="recommended-heading">
            <h2 id="recommended-heading" class="font-display text-lg font-semibold text-pearl mb-4">Recommended for you</h2>
            <ul class="grid sm:grid-cols-2 gap-4" role="list">
                @foreach ($recommended as $row)
                    @php $recJob = $row['job']; $match = $row['match']; @endphp
                    <li>
                        <article class="glass-card p-5 h-full flex flex-col">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <h3 class="font-medium text-pearl">
                                    <a href="{{ route('va.jobs.show', $recJob) }}" class="hover:text-teal transition-colors">{{ $recJob->title }}</a>
                                </h3>
                                @if ($match)
                                    <x-badge variant="verified">{{ round($match['score']) }}% match</x-badge>
                                @endif
                            </div>
                            @if ($recJob->category)
                                <p class="text-xs text-pearl/50 mb-2">{{ $recJob->category->name }}</p>
                            @endif
                            <p class="text-sm text-pearl/60 flex-1 line-clamp-2">{{ Str::limit($recJob->description, 100) }}</p>
                            <div class="mt-4 flex items-center justify-between">
                                <span class="text-sm text-pearl/70">
                                    @if ($recJob->budget_amount)
                                        {{ $recJob->currency }} {{ number_format($recJob->budget_amount, 2) }}
                                    @elseif ($recJob->hourly_rate)
                                        {{ $recJob->currency }} {{ number_format($recJob->hourly_rate, 2) }}/hr
                                    @endif
                                </span>
                                <a href="{{ route('va.jobs.show', $recJob) }}" class="btn-secondary !py-2 !px-4 text-sm">View</a>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section aria-labelledby="all-jobs-heading">
        <h2 id="all-jobs-heading" class="font-display text-lg font-semibold text-pearl mb-4">All open jobs</h2>

        @if ($jobs->isEmpty())
            <x-empty-state
                title="No jobs available"
                description="Check back soon for new opportunities matching your skills."
                icon="🔍"
            />
        @else
            <ul class="space-y-4" role="list">
                @foreach ($jobs as $job)
                    <li>
                        <article class="glass-card p-6">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                <div class="min-w-0">
                                    <h3 class="font-display text-lg font-semibold text-pearl mb-1">
                                        <a href="{{ route('va.jobs.show', $job) }}" class="hover:text-teal transition-colors">{{ $job->title }}</a>
                                    </h3>
                                    <p class="text-sm text-pearl/60 mb-2">{{ $job->client->name ?? 'Client' }}</p>
                                    <p class="text-sm text-pearl/70 line-clamp-2 mb-3">{{ Str::limit($job->description, 140) }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        @if ($job->category)
                                            <x-badge variant="coral">{{ $job->category->name }}</x-badge>
                                        @endif
                                        <x-badge>{{ ucfirst($job->job_type) }}</x-badge>
                                    </div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-pearl font-medium mb-2">
                                        @if ($job->budget_amount)
                                            {{ $job->currency }} {{ number_format($job->budget_amount, 2) }}
                                        @elseif ($job->hourly_rate)
                                            {{ $job->currency }} {{ number_format($job->hourly_rate, 2) }}/hr
                                        @endif
                                    </p>
                                    <a href="{{ route('va.jobs.show', $job) }}" class="btn-primary">View details</a>
                                </div>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>

            @if ($jobs->hasPages())
                <nav class="mt-8" aria-label="Jobs pagination">
                    {{ $jobs->links() }}
                </nav>
            @endif
        @endif
    </section>
@endsection
