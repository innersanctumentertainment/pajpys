<?php

namespace App\Models;

use App\Enums\LedgerEntryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LedgerEntry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ledger_account_id',
        'entry_type',
        'amount_minor',
        'currency',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'description',
        'metadata',
        'recorded_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_type' => LedgerEntryType::class,
            'metadata' => 'array',
            'recorded_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'ledger_account_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
