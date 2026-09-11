<?php

namespace App\Http\Requests\Va;

use Illuminate\Foundation\Http\FormRequest;

class AcceptJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isVaApproved() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cover_letter' => ['nullable', 'string', 'max:5000'],
            'proposed_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
