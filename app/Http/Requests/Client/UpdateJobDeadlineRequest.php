<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobDeadlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isClient() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'starts_at' => ['nullable', 'date', 'after_or_equal:today'],
            'deadline_at' => ['nullable', 'date', 'after:starts_at'],
        ];
    }
}
