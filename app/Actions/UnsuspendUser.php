<?php

namespace App\Actions;

use App\Models\User;

class UnsuspendUser
{
    /**
     * Unsuspend the given user.
     */
    public function handle(User $user): User
    {
        $user->update(['is_suspended' => false]);

        return $user;
    }
}
