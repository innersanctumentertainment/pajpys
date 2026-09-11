<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VaProfile;

class VaProfilePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, VaProfile $vaProfile): bool
    {
        if ($vaProfile->status === 'approved') {
            return true;
        }

        return $user !== null && $user->id === $vaProfile->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isVa() && $user->vaProfile === null;
    }

    public function update(User $user, VaProfile $vaProfile): bool
    {
        return $user->id === $vaProfile->user_id;
    }

    public function delete(User $user, VaProfile $vaProfile): bool
    {
        return $user->id === $vaProfile->user_id;
    }
}
