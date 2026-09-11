<?php

namespace App\Http\Requests\Va;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVaProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isVa() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['nullable', 'string', 'max:255'],
            'headline' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'hourly_rate_min' => ['nullable', 'numeric', 'min:0'],
            'hourly_rate_max' => ['nullable', 'numeric', 'gte:hourly_rate_min'],
            'currency' => ['nullable', 'string', 'size:3'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'availability_status' => ['nullable', 'string', 'in:available,busy,away'],
        ];
    }
}
