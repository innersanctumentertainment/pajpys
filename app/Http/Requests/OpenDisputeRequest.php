<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenDisputeRequest extends FormRequest
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
            'against_user_id' => ['required', 'integer', 'exists:users,id'],
            'reason_code' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:10000'],
            'disputed_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
