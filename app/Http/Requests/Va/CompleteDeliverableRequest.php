<?php

namespace App\Http\Requests\Va;

use Illuminate\Foundation\Http\FormRequest;

class CompleteDeliverableRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
