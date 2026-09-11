@extends('layouts.dashboard')

@section('title', 'My Jobs')
@section('page_title', 'My Jobs')

@section('sidebar')
    @include('partials.dashboard-sidebar', ['active' => 'client.jobs'])
@endsection

@section('content')
    <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <p class="text-pearl/60 text-sm">Manage your job postings and drafts</p>
        </div>
        <a href="{{ route('client.jobs.index') }}#create-job" class="btn-primary">Post a Job</a>
    </header>

    @if ($jobs->isEmpty())
        <x-empty-state
            title="No jobs yet"
            description="Create your first job posting to find a virtual assistant."
            icon="💼"
        >
            <x-slot:action>
                <a href="#create-job" class="btn-primary">Create Job</a>
            </x-slot:action>
        </x-empty-state>
    @else
        <ul class="space-y-4" role="list" aria-label="Your jobs">
            @foreach ($jobs as $job)
                <li>
                    <article class="glass-card p-6 hover:border-teal/30 transition-colors">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <h2 class="font-display text-lg font-semibold text-pearl mb-1">
                                    <a href="{{ route('client.jobs.show', $job) }}" class="hover:text-teal transition-colors">
                                        {{ $job->title }}
                                    </a>
                                </h2>
                                <p class="text-pearl/60 text-sm mb-3 line-clamp-2">{{ Str::limit($job->description, 120) }}</p>
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-badge>{{ str_replace('_', ' ', ucfirst($job->status->value)) }}</x-badge>
                                    @if ($job->category)
                                        <x-badge variant="coral">{{ $job->category->name }}</x-badge>
                                    @endif
                                    @if ($job->budget_amount)
                                        <span class="text-sm text-pearl/70">{{ $job->currency }} {{ number_format($job->budget_amount, 2) }}</span>
                                    @elseif ($job->hourly_rate)
                                        <span class="text-sm text-pearl/70">{{ $job->currency }} {{ number_format($job->hourly_rate, 2) }}/hr</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <a href="{{ route('client.jobs.show', $job) }}" class="btn-secondary">View</a>
                                @if (in_array($job->status->value, ['draft', 'pending_payment'], true))
                                    <a href="{{ route('client.jobs.show', $job) }}" class="btn-primary">Continue</a>
                                @endif
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

    <section id="create-job" class="mt-12" aria-labelledby="create-job-heading">
        <x-card>
            <x-slot:header>
                <h2 id="create-job-heading" class="font-display text-lg font-semibold text-pearl">Create a new job</h2>
            </x-slot:header>

            <form method="POST" action="{{ route('client.jobs.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="title" class="form-label">Job title</label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}" required maxlength="255" class="form-input" autocomplete="off">
                    @error('title')<p class="mt-1 text-sm text-coral">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" required maxlength="10000" class="form-textarea">{{ old('description') }}</textarea>
                    @error('description')<p class="mt-1 text-sm text-coral">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="requirements" class="form-label">Requirements <span class="text-pearl/50">(optional)</span></label>
                    <textarea id="requirements" name="requirements" maxlength="10000" class="form-textarea">{{ old('requirements') }}</textarea>
                </div>

                <div class="grid sm:grid-cols-2 gap-5">
                    <div>
                        <label for="job_type" class="form-label">Job type</label>
                        <select id="job_type" name="job_type" required class="form-select">
                            <option value="fixed" @selected(old('job_type') === 'fixed')>Fixed price</option>
                            <option value="hourly" @selected(old('job_type') === 'hourly')>Hourly</option>
                        </select>
                    </div>

                    <div>
                        <label for="category_id" class="form-label">Category <span class="text-pearl/50">(optional)</span></label>
                        <select id="category_id" name="category_id" class="form-select">
                            <option value="">Select category</option>
                            @foreach (\App\Models\Category::query()->where('is_active', true)->orderBy('sort_order')->get() as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn-primary">Create draft</button>
                </div>
            </form>
        </x-card>
    </section>
@endsection
