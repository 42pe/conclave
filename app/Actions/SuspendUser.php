<?php

namespace App\Actions;

use App\Models\User;

class SuspendUser
{
    /**
     * Suspend the given user.
     */
    public function handle(User $user): User
    {
        $user->update(['is_suspended' => true]);

        return $user;
    }
}
