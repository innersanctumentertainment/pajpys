<?php

namespace App\Http\Concerns;

use App\Models\MarketplaceJob;
use App\Models\User;

trait AuthorizesMarketplaceJob
{
    protected function authorizeClientJob(User $user, MarketplaceJob $job): void
    {
        if ($job->client_id !== $user->id) {
            abort(403, 'You do not own this job.');
        }
    }

    protected function authorizeJobParticipant(User $user, MarketplaceJob $job): void
    {
        $isClient = $job->client_id === $user->id;
        $isAssignedVa = $job->assignments()
            ->where('va_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();

        if (! $isClient && ! $isAssignedVa) {
            abort(403, 'You are not a participant on this job.');
        }
    }
}
