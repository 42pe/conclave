<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Str;

class DeleteUser
{
    /**
     * Anonymize the given user (soft delete).
     *
     * Sets is_deleted, clears personal info, and disables login.
     * Does NOT hard delete — content stays intact.
     */
    public function handle(User $user): User
    {
        $user->update([
            'is_deleted' => true,
            'name' => 'Deleted User',
            'first_name' => null,
            'last_name' => null,
            'preferred_name' => null,
            'bio' => null,
            'avatar_path' => null,
            'email' => "deleted_{$user->id}@deleted.local",
            'password' => Str::random(64),
            'deleted_at' => now(),
        ]);

        return $user;
    }
}
