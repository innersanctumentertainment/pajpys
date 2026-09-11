<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'requirements',
        'job_type',
        'default_budget_amount',
        'default_budget_amount_minor',
        'currency',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'default_budget_amount' => 'decimal:4',
            'is_public' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function templateSkills(): HasMany
    {
        return $this->hasMany(JobTemplateSkill::class);
    }
}
