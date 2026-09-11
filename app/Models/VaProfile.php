<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VaProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'display_name',
        'headline',
        'bio',
        'phone',
        'country_code',
        'timezone',
        'avatar_path',
        'hourly_rate_min',
        'hourly_rate_max',
        'currency',
        'years_experience',
        'availability_status',
        'is_featured',
        'is_verified',
        'average_rating',
        'completed_jobs_count',
        'status',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate_min' => 'decimal:4',
            'hourly_rate_max' => 'decimal:4',
            'is_featured' => 'boolean',
            'is_verified' => 'boolean',
            'average_rating' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function portfolioItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PortfolioItem::class, 'user_id', 'user_id');
    }

    public function availabilitySchedules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AvailabilitySchedule::class, 'user_id', 'user_id');
    }

    public function isAvailable(): bool
    {
        return $this->availability_status === 'available' && $this->status === 'approved';
    }
}
