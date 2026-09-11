<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payer_id',
        'payee_id',
        'marketplace_job_id',
        'wallet_id',
        'payment_type',
        'status',
        'amount',
        'amount_minor',
        'currency',
        'fee_amount',
        'fee_amount_minor',
        'net_amount',
        'net_amount_minor',
        'gateway',
        'gateway_reference',
        'idempotency_key',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'decimal:4',
            'fee_amount' => 'decimal:4',
            'net_amount' => 'decimal:4',
            'paid_at' => 'datetime',
        ];
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function marketplaceJob(): BelongsTo
    {
        return $this->belongsTo(MarketplaceJob::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    public function gatewayEvents(): HasMany
    {
        return $this->hasMany(PaymentGatewayEvent::class);
    }
}
