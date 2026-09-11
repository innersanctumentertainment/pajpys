<x-card>
    <x-slot:header>
        <h2 class="font-display text-lg font-semibold text-pearl">Basic information</h2>
    </x-slot:header>

    <form method="POST" action="{{ route('client.jobs.wizard.basic', $job) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label for="title" class="form-label">Job title</label>
            <input type="text" id="title" name="title" value="{{ old('title', $job->title) }}" required maxlength="255" class="form-input">
            @error('title')<p class="mt-1 text-sm text-coral">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" required maxlength="10000" class="form-textarea">{{ old('description', $job->description) }}</textarea>
            @error('description')<p class="mt-1 text-sm text-coral">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="requirements" class="form-label">Requirements <span class="text-pearl/50">(optional)</span></label>
            <textarea id="requirements" name="requirements" maxlength="10000" class="form-textarea">{{ old('requirements', $job->requirements) }}</textarea>
        </div>

        <div class="grid sm:grid-cols-2 gap-5">
            <div>
                <label for="job_type" class="form-label">Job type</label>
                <select id="job_type" name="job_type" required class="form-select">
                    <option value="fixed" @selected(old('job_type', $job->job_type) === 'fixed')>Fixed price</option>
                    <option value="hourly" @selected(old('job_type', $job->job_type) === 'hourly')>Hourly</option>
                </select>
            </div>

            <div>
                <label for="category_id" class="form-label">Category</label>
                <select id="category_id" name="category_id" class="form-select">
                    <option value="">Select category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $job->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($skills->isNotEmpty())
            <fieldset>
                <legend class="form-label mb-3">Skills</legend>
                <div class="grid sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto">
                    @php
                        $selectedSkills = old('skill_ids', $job->jobSkills->pluck('skill_id')->all());
                        $requiredSkills = old('required_skill_ids', $job->jobSkills->where('is_required', true)->pluck('skill_id')->all());
                    @endphp
                    @foreach ($skills as $skill)
                        <label class="flex items-center gap-2 text-sm text-pearl/80 cursor-pointer">
                            <input type="checkbox" name="skill_ids[]" value="{{ $skill->id }}" @checked(in_array($skill->id, $selectedSkills))>
                            {{ $skill->name }}
                            <input type="checkbox" name="required_skill_ids[]" value="{{ $skill->id }}" @checked(in_array($skill->id, $requiredSkills)) class="ml-auto" title="Required">
                            <span class="text-xs text-pearl/40">req</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endif

        <button type="submit" class="btn-primary">Save &amp; continue</button>
    </form>
</x-card>
