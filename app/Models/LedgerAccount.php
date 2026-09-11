<?php

namespace App\Models;

use App\Enums\LedgerAccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LedgerAccount extends Model
{
    protected $fillable = [
        'code',
        'name',
        'account_type',
        'wallet_id',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'account_type' => LedgerAccountType::class,
            'is_active' => 'boolean',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
