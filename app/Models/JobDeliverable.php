<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobDeliverable extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'marketplace_job_id',
        'job_assignment_id',
        'submitted_by',
        'title',
        'description',
        'status',
        'submitted_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(MarketplaceJob::class, 'marketplace_job_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(JobAssignment::class, 'job_assignment_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(JobCompletionEvidence::class);
    }
}
