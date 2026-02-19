<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class UserProfileController extends Controller
{
    public function show(string $username): Response
    {
        $profileUser = User::where('username', $username)->firstOrFail();

        $discussions = $profileUser->discussions()
            ->with(['topic:id,title,slug', 'location:id,name'])
            ->latest()
            ->paginate(10, ['*'], 'discussions_page');

        $replies = $profileUser->replies()
            ->with(['discussion:id,title,slug,topic_id', 'discussion.topic:id,title,slug'])
            ->latest()
            ->paginate(10, ['*'], 'replies_page');

        return Inertia::render('users/show', [
            'profileUser' => $profileUser->only([
                'id', 'username', 'name', 'first_name', 'last_name',
                'preferred_name', 'bio', 'avatar_path', 'role',
                'is_deleted', 'is_suspended', 'display_name',
                'show_real_name', 'show_email', 'email',
                'created_at',
            ]),
            'discussions' => $discussions,
            'replies' => $replies,
        ]);
    }
}
