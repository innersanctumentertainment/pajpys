<?php

namespace App\Services;

use App\Models\JobCandidate;
use App\Models\MarketplaceJob;
use App\Models\User;
use Illuminate\Support\Collection;

class CandidatePrivacyService
{
    /**
     * @return Collection<int, JobCandidate>
     */
    public function visibleCandidatesFor(MarketplaceJob $job, User $viewer): Collection
    {
        if ($viewer->id === $job->client_id || $viewer->hasAnyRole(['master_admin', 'staff'])) {
            return JobCandidate::query()
                ->where('marketplace_job_id', $job->id)
                ->get();
        }

        if ($viewer->isVa()) {
            return JobCandidate::query()
                ->where('marketplace_job_id', $job->id)
                ->where('va_id', $viewer->id)
                ->get();
        }

        return collect();
    }

    public function canViewCandidate(User $viewer, JobCandidate $candidate): bool
    {
        return $this->visibleCandidatesFor($candidate->job, $viewer)
            ->contains('id', $candidate->id);
    }
}
