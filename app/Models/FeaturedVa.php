<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturedVa extends Model
{
    protected $fillable = [
        'va_profile_id',
        'sort_order',
        'featured_from',
        'featured_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'featured_from' => 'datetime',
            'featured_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function vaProfile(): BelongsTo
    {
        return $this->belongsTo(VaProfile::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('featured_from')->orWhere('featured_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('featured_until')->orWhere('featured_until', '>=', now());
            });
    }
}
