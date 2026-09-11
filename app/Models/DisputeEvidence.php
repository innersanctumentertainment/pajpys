<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisputeEvidence extends Model
{
    use SoftDeletes;

    protected $table = 'dispute_evidence';

    protected $fillable = [
        'dispute_id',
        'uploaded_by',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'description',
    ];

    public function dispute(): BelongsTo
    {
        return $this->belongsTo(Dispute::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
