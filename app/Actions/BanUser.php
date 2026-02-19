<?php

namespace App\Actions;

use App\Models\BannedEmail;
use App\Models\User;

class BanUser
{
    public function __construct(private DeleteUser $deleteUser) {}

    public function handle(User $user, User $bannedBy, ?string $reason = null): void
    {
        $email = strtolower($user->email);

        // Add email to banned list
        BannedEmail::create([
            'email' => $email,
            'user_id' => $user->id,
            'banned_by' => $bannedBy->id,
            'reason' => $reason,
        ]);

        // Delete (anonymize) the user
        $this->deleteUser->handle($user);
    }
}
