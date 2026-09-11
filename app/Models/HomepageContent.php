<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HomepageContent extends Model
{
    use SoftDeletes;

    protected $table = 'homepage_content';

    protected $fillable = [
        'section_key',
        'title',
        'subtitle',
        'body',
        'metadata',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
