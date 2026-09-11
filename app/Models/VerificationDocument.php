<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VerificationDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'verification_record_id',
        'document_type',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'status',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(VerificationRecord::class, 'verification_record_id');
    }
}
