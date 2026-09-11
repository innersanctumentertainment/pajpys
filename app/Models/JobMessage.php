<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobMessage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'marketplace_job_id',
        'sender_id',
        'recipient_id',
        'body',
        'is_system',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(MarketplaceJob::class, 'marketplace_job_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
