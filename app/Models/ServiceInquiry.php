<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceInquiry extends Model
{
    protected $fillable = [
        'service_listing_id',
        'client_id',
        'provider_id',
        'message',
        'status',
        'provider_response',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function serviceListing(): BelongsTo
    {
        return $this->belongsTo(ServiceListing::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
