<?php

namespace App\Policies;

use App\Models\ClientProfile;
use App\Models\User;

class ClientProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isClient();
    }

    public function view(User $user, ClientProfile $clientProfile): bool
    {
        return $user->id === $clientProfile->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isClient() && $user->clientProfile === null;
    }

    public function update(User $user, ClientProfile $clientProfile): bool
    {
        return $user->id === $clientProfile->user_id;
    }

    public function delete(User $user, ClientProfile $clientProfile): bool
    {
        return $user->id === $clientProfile->user_id;
    }
}
