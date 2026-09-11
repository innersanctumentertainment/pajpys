<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ResolveDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('disputes.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:resolved_client,resolved_va,resolved_split,closed'],
            'resolution_notes' => ['required', 'string', 'max:10000'],
        ];
    }
}
