<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'account_holder_name',
        'bank_name',
        'account_number_encrypted',
        'routing_number_encrypted',
        'swift_code_encrypted',
        'account_type',
        'currency',
        'is_default',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
