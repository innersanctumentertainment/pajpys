<?php

namespace App\Policies;

use App\Models\MarketplaceJob;
use App\Models\User;

class MarketplaceJobPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, MarketplaceJob $job): bool
    {
        if ($job->visibility === 'public') {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $user->id === $job->client_id
            || $user->hasAnyRole(['master_admin', 'staff']);
    }

    public function create(User $user): bool
    {
        return $user->isClient() && $user->hasVerifiedEmail();
    }

    public function update(User $user, MarketplaceJob $job): bool
    {
        return $user->id === $job->client_id;
    }

    public function delete(User $user, MarketplaceJob $job): bool
    {
        return $user->id === $job->client_id
            || $user->hasRole('master_admin');
    }
}
