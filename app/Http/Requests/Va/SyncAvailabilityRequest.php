<?php

namespace App\Http\Requests\Va;

use Illuminate\Foundation\Http\FormRequest;

class SyncAvailabilityRequest extends FormRequest
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
            'schedules' => ['required', 'array'],
            'schedules.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.timezone' => ['nullable', 'string', 'max:64'],
            'schedules.*.is_available' => ['nullable', 'boolean'],
        ];
    }
}
