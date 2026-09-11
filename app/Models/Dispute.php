<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dispute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'marketplace_job_id',
        'payment_id',
        'raised_by',
        'against_user_id',
        'status',
        'reason_code',
        'description',
        'disputed_amount',
        'disputed_amount_minor',
        'currency',
        'assigned_to',
        'resolution_notes',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'disputed_amount' => 'decimal:4',
            'resolved_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(MarketplaceJob::class, 'marketplace_job_id');
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }
}
