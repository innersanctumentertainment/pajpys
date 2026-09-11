<?php

namespace App\Http\Requests\Va;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array', 'min:1'],
            'preferences.*.channel' => ['required', 'string', 'in:email,database,push'],
            'preferences.*.notification_type' => ['required', 'string', 'max:100'],
            'preferences.*.is_enabled' => ['required', 'boolean'],
        ];
    }
}
