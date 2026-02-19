<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DeleteUser
{
    public function handle(User $user): void
    {
        // Delete avatar if it exists
        if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        // Anonymize personal info
        $user->update([
            'name' => 'Deleted User',
            'first_name' => null,
            'last_name' => null,
            'preferred_name' => null,
            'bio' => null,
            'avatar_path' => null,
            'email' => 'deleted_'.Str::uuid().'@deleted.local',
            'password' => Str::random(60),
            'is_deleted' => true,
            'deleted_at' => now(),
            'show_real_name' => false,
            'show_email' => false,
            'show_in_directory' => false,
        ]);
    }
}
