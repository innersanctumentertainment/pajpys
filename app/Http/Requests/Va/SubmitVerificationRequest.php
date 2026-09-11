<?php

namespace App\Http\Requests\Va;

use Illuminate\Foundation\Http\FormRequest;

class SubmitVerificationRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:identity,address,professional'],
            'documents' => ['required', 'array', 'min:1'],
            'documents.*.document_type' => ['required', 'string', 'max:50'],
            'documents.*.file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ];
    }
}
