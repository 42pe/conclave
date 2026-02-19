<?php

namespace App\Actions;

use App\Models\BannedEmail;
use App\Models\User;

class BanUser
{
    public function __construct(private DeleteUser $deleteUser) {}

    /**
     * Ban the given user: anonymize and add email to banned list.
     */
    public function handle(User $user, User $bannedBy, ?string $reason = null): BannedEmail
    {
        $originalEmail = $user->email;

        $this->deleteUser->handle($user);

        return BannedEmail::create([
            'email' => $originalEmail,
            'user_id' => $user->id,
            'banned_by' => $bannedBy->id,
            'reason' => $reason,
        ]);
    }
}
