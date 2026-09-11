<x-card>
    <x-slot:header>
        <h2 class="font-display text-lg font-semibold text-pearl">Review &amp; publish</h2>
    </x-slot:header>

    @if (! empty($validationErrors))
        <div class="mb-6 p-4 rounded-lg border border-coral/30 bg-coral/10" role="alert">
            <p class="font-medium text-pearl mb-2">Please fix the following before publishing:</p>
            <ul class="list-disc list-inside text-sm text-pearl/80 space-y-1">
                @foreach ($validationErrors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <dl class="grid sm:grid-cols-2 gap-4 text-sm mb-6">
        <div>
            <dt class="text-pearl/50 mb-1">Title</dt>
            <dd class="text-pearl">{{ $job->title }}</dd>
        </div>
        <div>
            <dt class="text-pearl/50 mb-1">Type</dt>
            <dd class="text-pearl">{{ ucfirst($job->job_type) }}</dd>
        </div>
        <div>
            <dt class="text-pearl/50 mb-1">Budget</dt>
            <dd class="text-pearl">
                @if ($job->budget_amount)
                    {{ $job->currency }} {{ number_format($job->budget_amount, 2) }}
                @elseif ($job->hourly_rate)
                    {{ $job->currency }} {{ number_format($job->hourly_rate, 2) }}/hr
                @else
                    <span class="text-coral">Not set</span>
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-pearl/50 mb-1">Deadline</dt>
            <dd class="text-pearl">
                @if ($job->deadline_at)
                    {{ $job->deadline_at->format('M j, Y g:i A') }}
                @else
                    <span class="text-coral">Not set</span>
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-pearl/50 mb-1">Visibility</dt>
            <dd class="text-pearl">{{ $job->visibility ? str_replace('_', ' ', ucfirst($job->visibility)) : 'Not set' }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-pearl/50 mb-1">Description</dt>
            <dd class="text-pearl/80 whitespace-pre-wrap">{{ $job->description }}</dd>
        </div>
    </dl>

    @if (empty($validationErrors))
        <form method="POST" action="{{ route('client.jobs.publish', $job) }}">
            @csrf
            <button type="submit" class="btn-primary">Publish job</button>
        </form>
    @else
        <a href="{{ route('client.jobs.show', $job) }}" class="btn-secondary">Back to wizard</a>
    @endif
</x-card>
