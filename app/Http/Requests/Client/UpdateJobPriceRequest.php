<?php

namespace App\Http\Requests\Client;

use App\Services\PlatformSettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isClient() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('currency')) {
            $this->merge([
                'currency' => app(PlatformSettingsService::class)->defaultCurrency(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $allowed = app(PlatformSettingsService::class)->allowedCurrencies();

        return [
            'budget_amount' => ['required_without:hourly_rate', 'numeric', 'min:0'],
            'hourly_rate' => ['required_without:budget_amount', 'numeric', 'min:0'],
            'estimated_hours' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'currency' => ['required', 'string', 'size:3', Rule::in($allowed)],
        ];
    }
}
