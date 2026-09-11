<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobTemplateSkill extends Model
{
    protected $fillable = [
        'job_template_id',
        'skill_id',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(JobTemplate::class, 'job_template_id');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
