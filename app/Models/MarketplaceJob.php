<?php

namespace App\Models;

use App\Enums\JobStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketplaceJob extends Model
{
    use SoftDeletes;

    protected $table = 'marketplace_jobs';

    protected $fillable = [
        'client_id',
        'category_id',
        'title',
        'slug',
        'description',
        'requirements',
        'job_type',
        'status',
        'visibility',
        'budget_amount',
        'currency',
        'budget_amount_minor',
        'hourly_rate',
        'estimated_hours',
        'starts_at',
        'deadline_at',
        'published_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => JobStatus::class,
            'budget_amount' => 'decimal:4',
            'hourly_rate' => 'decimal:4',
            'starts_at' => 'datetime',
            'deadline_at' => 'datetime',
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function jobSkills(): HasMany
    {
        return $this->hasMany(JobSkill::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(JobAssignment::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(JobCandidate::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(JobInvitation::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(JobMessage::class);
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(JobDeliverable::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }
}
