<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
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
            'reportable_type' => ['required', 'string', 'in:user,marketplace_job,review,message'],
            'reportable_id' => ['required', 'integer', 'min:1'],
            'reason_code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
