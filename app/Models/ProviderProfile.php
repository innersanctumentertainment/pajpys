<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'display_name',
        'headline',
        'bio',
        'location',
        'avatar_path',
        'status',
        'is_verified',
        'average_rating',
        'review_count',
        'completed_services_count',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'average_rating' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function serviceListings(): HasMany
    {
        return $this->hasMany(ServiceListing::class, 'user_id', 'user_id');
    }
}
