@extends('layouts.dashboard')

@section('title', $job->title)
@section('page_title', $job->title)

@section('sidebar')
    @include('partials.dashboard-sidebar', ['active' => 'client.jobs'])
@endsection

@section('content')
    @php
        $isEditable = in_array($job->status->value, ['draft', 'pending_payment'], true);
        $steps = ['basic' => 'Basic info', 'price' => 'Pricing', 'deadline' => 'Schedule', 'visibility' => 'Visibility', 'review' => 'Review'];
        $stepKeys = array_keys($steps);
        $currentIndex = array_search($wizardStep, $stepKeys, true);
    @endphp

    <header class="mb-8">
        <div class="flex flex-wrap items-center gap-3 mb-2">
            <x-badge>{{ str_replace('_', ' ', ucfirst($job->status->value)) }}</x-badge>
            @if ($job->category)
                <x-badge variant="coral">{{ $job->category->name }}</x-badge>
            @endif
        </div>
        <p class="text-pearl/60 text-sm">{{ ucfirst($job->job_type) }} job · {{ $job->currency }}</p>
    </header>

    @if ($isEditable)
        <nav aria-label="Job setup wizard" class="mb-8">
            <ol class="flex flex-wrap gap-2">
                @foreach ($steps as $key => $label)
                    @php $index = array_search($key, $stepKeys, true); @endphp
                    <li>
                        @if ($key === $wizardStep)
                            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-teal/20 text-teal text-sm font-medium" aria-current="step">
                                <span class="step-number !w-6 !h-6 !text-xs">{{ $index + 1 }}</span>
                                {{ $label }}
                            </span>
                        @elseif ($index < $currentIndex)
                            <a href="{{ $key === 'review' ? route('client.jobs.wizard.review', $job) : route('client.jobs.show', $job) }}"
                               class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-pearl/60 hover:text-pearl text-sm">
                                <span class="step-number !w-6 !h-6 !text-xs">{{ $index + 1 }}</span>
                                {{ $label }}
                            </a>
                        @else
                            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-pearl/40 text-sm">
                                <span class="step-number !w-6 !h-6 !text-xs opacity-50">{{ $index + 1 }}</span>
                                {{ $label }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>

        @include('client.jobs.wizard.'.$wizardStep)
    @else
        <article class="glass-card p-6 mb-6">
            <h2 class="font-display text-lg font-semibold text-pearl mb-4">Job details</h2>
            <dl class="grid sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-pearl/50 mb-1">Description</dt>
                    <dd class="text-pearl/80 whitespace-pre-wrap">{{ $job->description }}</dd>
                </div>
                @if ($job->requirements)
                    <div>
                        <dt class="text-pearl/50 mb-1">Requirements</dt>
                        <dd class="text-pearl/80 whitespace-pre-wrap">{{ $job->requirements }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-pearl/50 mb-1">Budget</dt>
                    <dd class="text-pearl">
                        @if ($job->budget_amount)
                            {{ $job->currency }} {{ number_format($job->budget_amount, 2) }}
                        @elseif ($job->hourly_rate)
                            {{ $job->currency }} {{ number_format($job->hourly_rate, 2) }}/hr
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-pearl/50 mb-1">Deadline</dt>
                    <dd class="text-pearl">{{ $job->deadline_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                </div>
            </dl>
        </article>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('client.jobs.candidates.index', $job) }}" class="btn-primary">View candidates</a>
            <a href="{{ route('jobs.messages.index', $job) }}" class="btn-secondary">Messages</a>
            @if ($job->status === \App\Enums\JobStatus::Open)
                <form method="POST" action="{{ route('client.jobs.go-live', $job) }}">
                    @csrf
                    <button type="submit" class="btn-primary">Go live</button>
                </form>
            @endif
        </div>
    @endif

    @if (in_array($job->status->value, ['draft', 'pending_payment', 'cancelled'], true))
        <form method="POST" action="{{ route('client.jobs.destroy', $job) }}" class="mt-8" onsubmit="return confirm('Delete this job?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-secondary text-coral border-coral/30">Delete job</button>
        </form>
    @endif
@endsection
