<x-card>
    <x-slot:header>
        <h2 class="font-display text-lg font-semibold text-pearl">Schedule</h2>
    </x-slot:header>

    <form method="POST" action="{{ route('client.jobs.wizard.deadline', $job) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="grid sm:grid-cols-2 gap-5">
            <div>
                <label for="starts_at" class="form-label">Start date <span class="text-pearl/50">(optional)</span></label>
                <input type="datetime-local" id="starts_at" name="starts_at"
                       value="{{ old('starts_at', $job->starts_at?->format('Y-m-d\TH:i')) }}"
                       class="form-input">
                @error('starts_at')<p class="mt-1 text-sm text-coral">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="deadline_at" class="form-label">Deadline</label>
                <input type="datetime-local" id="deadline_at" name="deadline_at"
                       value="{{ old('deadline_at', $job->deadline_at?->format('Y-m-d\TH:i')) }}"
                       class="form-input" required>
                @error('deadline_at')<p class="mt-1 text-sm text-coral">{{ $message }}</p>@enderror
            </div>
        </div>

        <button type="submit" class="btn-primary">Save &amp; continue</button>
    </form>
</x-card>
