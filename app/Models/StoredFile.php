<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoredFile extends Model
{
    use HasUuids;

    protected $fillable = [
        'owner_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'visibility',
        'allowed_user_ids',
    ];

    protected function casts(): array
    {
        return [
            'allowed_user_ids' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
