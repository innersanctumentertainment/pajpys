<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CancellationRecord extends Model
{
    protected $fillable = [
        'marketplace_job_id',
        'job_assignment_id',
        'cancelled_by',
        'cancelled_by_role',
        'reason_code',
        'reason_detail',
        'fee_amount',
        'fee_amount_minor',
        'currency',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'fee_amount' => 'decimal:4',
            'cancelled_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(MarketplaceJob::class, 'marketplace_job_id');
    }
}
