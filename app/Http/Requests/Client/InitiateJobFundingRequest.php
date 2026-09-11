<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class InitiateJobFundingRequest extends FormRequest
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
            'idempotency_key' => ['required', 'string', 'max:255'],
        ];
    }
}
