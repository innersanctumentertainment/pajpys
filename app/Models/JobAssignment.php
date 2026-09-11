<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'marketplace_job_id',
        'va_id',
        'client_id',
        'job_candidate_id',
        'status',
        'assigned_at',
        'started_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(MarketplaceJob::class, 'marketplace_job_id');
    }

    public function va(): BelongsTo
    {
        return $this->belongsTo(User::class, 'va_id');
    }
}
