<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobAgreement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'marketplace_job_id',
        'job_assignment_id',
        'client_id',
        'va_id',
        'status',
        'agreed_amount',
        'agreed_amount_minor',
        'currency',
        'terms',
        'client_signed_at',
        'va_signed_at',
        'effective_at',
    ];

    protected function casts(): array
    {
        return [
            'agreed_amount' => 'decimal:4',
            'client_signed_at' => 'datetime',
            'va_signed_at' => 'datetime',
            'effective_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(MarketplaceJob::class, 'marketplace_job_id');
    }
}
