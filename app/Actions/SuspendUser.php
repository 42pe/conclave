<?php

namespace App\Actions;

use App\Models\User;

class SuspendUser
{
    public function suspend(User $user): void
    {
        $user->update(['is_suspended' => true]);
    }

    public function unsuspend(User $user): void
    {
        $user->update(['is_suspended' => false]);
    }
}
