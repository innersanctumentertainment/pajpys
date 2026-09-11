<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class InviteVaRequest extends FormRequest
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
            'va_id' => ['required', 'integer', 'exists:users,id'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
