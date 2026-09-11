<?php

namespace App\Http\Requests\Va;

use Illuminate\Foundation\Http\FormRequest;

class SyncVaSkillsRequest extends FormRequest
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
            'skills' => ['required', 'array', 'min:1'],
            'skills.*.skill_id' => ['required', 'integer', 'exists:skills,id'],
            'skills.*.proficiency_level' => ['nullable', 'string', 'in:beginner,intermediate,advanced,expert'],
            'skills.*.years_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
        ];
    }
}
