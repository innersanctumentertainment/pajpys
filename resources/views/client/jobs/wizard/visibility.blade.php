<x-card>
    <x-slot:header>
        <h2 class="font-display text-lg font-semibold text-pearl">Visibility</h2>
    </x-slot:header>

    <form method="POST" action="{{ route('client.jobs.wizard.visibility', $job) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <fieldset class="space-y-3">
            <legend class="form-label">Who can see this job?</legend>

            @foreach (['public' => 'Public — visible to all approved VAs', 'private' => 'Private — hidden from discovery', 'invite_only' => 'Invite only — VAs must be invited'] as $value => $label)
                <label class="flex items-start gap-3 p-4 rounded-lg border border-white/10 cursor-pointer hover:border-teal/30 transition-colors">
                    <input type="radio" name="visibility" value="{{ $value }}" @checked(old('visibility', $job->visibility) === $value) required class="mt-1">
                    <span class="text-sm text-pearl/80">{{ $label }}</span>
                </label>
            @endforeach
        </fieldset>
        @error('visibility')<p class="text-sm text-coral">{{ $message }}</p>@enderror

        <button type="submit" class="btn-primary">Save &amp; continue</button>
    </form>
</x-card>
