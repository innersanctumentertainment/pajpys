@extends('layouts.dashboard')

@section('title', $job->title)
@section('page_title', $job->title)

@section('sidebar')
    @include('partials.dashboard-sidebar', ['active' => 'va.jobs.discover'])
@endsection

@section('content')
    <article class="glass-card p-6 mb-6">
        <header class="mb-6">
            <div class="flex flex-wrap items-center gap-2 mb-3">
                @if ($job->category)
                    <x-badge variant="coral">{{ $job->category->name }}</x-badge>
                @endif
                <x-badge>{{ ucfirst($job->job_type) }}</x-badge>
                @if ($match)
                    <x-badge variant="verified">{{ round($match['score']) }}% match</x-badge>
                @endif
                @if ($hasApplied)
                    <x-badge variant="verified">Applied</x-badge>
                @endif
            </div>

            <dl class="grid sm:grid-cols-3 gap-4 text-sm">
                <div>
                    <dt class="text-pearl/50 mb-1">Budget</dt>
                    <dd class="text-pearl font-medium">
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
                    <dd class="text-pearl">{{ $job->deadline_at?->format('M j, Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-pearl/50 mb-1">Visibility</dt>
                    <dd class="text-pearl">{{ str_replace('_', ' ', ucfirst($job->visibility ?? 'public')) }}</dd>
                </div>
            </dl>
        </header>

        <section aria-labelledby="description-heading" class="mb-6">
            <h2 id="description-heading" class="font-display font-semibold text-pearl mb-2">Description</h2>
            <p class="text-pearl/80 whitespace-pre-wrap">{{ $job->description }}</p>
        </section>

        @if ($job->requirements)
            <section aria-labelledby="requirements-heading" class="mb-6">
                <h2 id="requirements-heading" class="font-display font-semibold text-pearl mb-2">Requirements</h2>
                <p class="text-pearl/80 whitespace-pre-wrap">{{ $job->requirements }}</p>
            </section>
        @endif

        @if ($job->jobSkills->isNotEmpty())
            <section aria-labelledby="skills-heading">
                <h2 id="skills-heading" class="font-display font-semibold text-pearl mb-2">Skills</h2>
                <ul class="flex flex-wrap gap-2" role="list">
                    @foreach ($job->jobSkills as $jobSkill)
                        <li>
                            <x-badge @if($jobSkill->is_required) variant="coral" @endif>
                                {{ $jobSkill->skill->name ?? 'Skill' }}
                                @if ($jobSkill->is_required) (required) @endif
                            </x-badge>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </article>

    @if (! $hasApplied && $job->status === \App\Enums\JobStatus::Accepting)
        <x-card>
            <x-slot:header>
                <h2 class="font-display text-lg font-semibold text-pearl">Apply for this job</h2>
            </x-slot:header>

            <form method="POST" action="{{ route('va.jobs.accept', $job) }}" class="space-y-5">
                @csrf

                <div>
                    <label for="cover_letter" class="form-label">Cover letter <span class="text-pearl/50">(optional)</span></label>
                    <textarea id="cover_letter" name="cover_letter" maxlength="5000" class="form-textarea" placeholder="Tell the client why you're a great fit...">{{ old('cover_letter') }}</textarea>
                    @error('cover_letter')<p class="mt-1 text-sm text-coral">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="proposed_amount" class="form-label">Proposed amount <span class="text-pearl/50">(optional)</span></label>
                    <input type="number" id="proposed_amount" name="proposed_amount" value="{{ old('proposed_amount') }}" min="0" step="0.01" class="form-input" inputmode="decimal">
                    @error('proposed_amount')<p class="mt-1 text-sm text-coral">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="btn-primary">Submit application</button>
            </form>
        </x-card>
    @elseif ($hasApplied)
        <p class="text-teal" role="status">You have already applied to this job.</p>
    @endif

    <div class="mt-6">
        <a href="{{ route('va.jobs.discover') }}" class="btn-secondary">Back to discovery</a>
    </div>
@endsection
