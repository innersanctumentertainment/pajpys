<x-card>
    <x-slot:header>
        <h2 class="font-display text-lg font-semibold text-pearl">Pricing</h2>
    </x-slot:header>

    <form method="POST" action="{{ route('client.jobs.wizard.price', $job) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="grid sm:grid-cols-2 gap-5">
            <div>
                <label for="budget_amount" class="form-label">Fixed budget</label>
                <input type="number" id="budget_amount" name="budget_amount" value="{{ old('budget_amount', $job->budget_amount) }}" min="0" step="0.01" class="form-input" inputmode="decimal">
                @error('budget_amount')<p class="mt-1 text-sm text-coral">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="hourly_rate" class="form-label">Hourly rate</label>
                <input type="number" id="hourly_rate" name="hourly_rate" value="{{ old('hourly_rate', $job->hourly_rate) }}" min="0" step="0.01" class="form-input" inputmode="decimal">
                @error('hourly_rate')<p class="mt-1 text-sm text-coral">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-5">
            <div>
                <label for="estimated_hours" class="form-label">Estimated hours <span class="text-pearl/50">(optional)</span></label>
                <input type="number" id="estimated_hours" name="estimated_hours" value="{{ old('estimated_hours', $job->estimated_hours) }}" min="1" class="form-input">
            </div>

            <div>
                <x-currency-select
                    name="currency"
                    :selected="old('currency', $job->currency ?? $defaultCurrency ?? 'TTD')"
                    :allowed="$allowedCurrencies ?? ['TTD']"
                    :default="$defaultCurrency ?? 'TTD'"
                />
                <p class="mt-1 text-xs text-pearl/50">Prices default to TTD. Additional currencies are configured in admin.</p>
            </div>
        </div>

        <p class="text-sm text-pearl/60">Provide either a fixed budget or an hourly rate.</p>

        <button type="submit" class="btn-primary">Save &amp; continue</button>
    </form>
</x-card>
