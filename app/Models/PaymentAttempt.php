<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    protected $fillable = [
        'payment_id',
        'attempt_number',
        'status',
        'gateway',
        'gateway_reference',
        'failure_reason',
        'request_payload',
        'response_payload',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'request_payload' => 'array',
            'response_payload' => 'array',
            'attempted_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
