<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PortfolioItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'media_path',
        'media_type',
        'external_url',
        'sort_order',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
