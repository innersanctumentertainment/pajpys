<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobCandidate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'marketplace_job_id',
        'va_id',
        'status',
        'cover_letter',
        'proposed_amount',
        'proposed_amount_minor',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'proposed_amount' => 'decimal:4',
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
