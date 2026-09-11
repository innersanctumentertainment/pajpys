<?php

namespace App\Models;

use App\Enums\ServiceListingStatus;
use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceListing extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'description',
        'deliverables',
        'service_type',
        'pricing_type',
        'price_amount',
        'price_amount_minor',
        'price_max_amount',
        'price_max_amount_minor',
        'currency',
        'delivery_days',
        'status',
        'is_va_service',
        'is_featured',
        'view_count',
        'inquiry_count',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ServiceListingStatus::class,
            'service_type' => ServiceType::class,
            'is_va_service' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'price_amount' => 'decimal:4',
            'price_max_amount' => 'decimal:4',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'service_listing_skills');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(ServiceInquiry::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', ServiceListingStatus::Active);
    }

    public function scopePublic($query)
    {
        return $query->active()->whereNotNull('published_at');
    }
}
