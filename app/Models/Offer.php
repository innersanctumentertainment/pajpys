<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'marketplace_job_id',
        'client_id',
        'va_id',
        'job_candidate_id',
        'status',
        'amount',
        'amount_minor',
        'currency',
        'message',
        'expires_at',
        'accepted_at',
        'declined_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(MarketplaceJob::class, 'marketplace_job_id');
    }
}
