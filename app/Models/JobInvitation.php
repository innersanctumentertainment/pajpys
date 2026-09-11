<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobInvitation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'marketplace_job_id',
        'client_id',
        'va_id',
        'status',
        'message',
        'responded_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(MarketplaceJob::class, 'marketplace_job_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function va(): BelongsTo
    {
        return $this->belongsTo(User::class, 'va_id');
    }
}
