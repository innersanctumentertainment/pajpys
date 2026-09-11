<?php

namespace App\Http\Requests\Va;

use Illuminate\Foundation\Http\FormRequest;

class SubmitCompletionEvidenceRequest extends FormRequest
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
            'deliverable_id' => ['nullable', 'integer', 'exists:job_deliverables,id'],
            'file' => ['required', 'file', 'max:20480'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
