<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AvatarUploadRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AvatarController extends Controller
{
    /**
     * Store the user's avatar.
     */
    public function store(AvatarUploadRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->storeAs(
            'avatars/'.$user->id,
            Str::uuid().'.'.$request->file('avatar')->extension(),
            'public'
        );

        $user->update(['avatar_path' => $path]);

        return to_route('profile.edit');
    }

    /**
     * Remove the user's avatar.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        return to_route('profile.edit');
    }
}
