<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    protected $fillable = [
        'base_currency',
        'quote_currency',
        'rate',
        'effective_at',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'effective_at' => 'datetime',
        ];
    }
}
