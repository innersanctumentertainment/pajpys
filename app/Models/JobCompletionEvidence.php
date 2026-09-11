<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobCompletionEvidence extends Model
{
    use SoftDeletes;

    protected $table = 'job_completion_evidence';

    protected $fillable = [
        'marketplace_job_id',
        'job_deliverable_id',
        'uploaded_by',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'notes',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(MarketplaceJob::class, 'marketplace_job_id');
    }

    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(JobDeliverable::class, 'job_deliverable_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
