<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class UserProfileController extends Controller
{
    /**
     * Display the specified user's profile.
     */
    public function show(User $user): Response
    {
        $profileData = [
            'id' => $user->id,
            'username' => $user->username,
            'display_name' => $user->display_name,
            'avatar_path' => $user->is_deleted ? null : $user->avatar_path,
            'role' => $user->is_deleted ? null : $user->role,
            'is_deleted' => $user->is_deleted,
            'is_suspended' => $user->is_suspended,
            'created_at' => $user->created_at,
        ];

        if (! $user->is_deleted) {
            $profileData['bio'] = $user->bio;

            if ($user->show_real_name) {
                $profileData['first_name'] = $user->first_name;
                $profileData['last_name'] = $user->last_name;
            }

            if ($user->show_email) {
                $profileData['email'] = $user->email;
            }
        }

        $discussions = $user->discussions()
            ->with(['topic:id,title,slug'])
            ->orderByDesc('created_at')
            ->paginate(10, ['id', 'topic_id', 'title', 'slug', 'reply_count', 'last_reply_at', 'created_at', 'is_pinned', 'is_locked']);

        $recentReplies = $user->replies()
            ->with(['discussion:id,title,slug,topic_id', 'discussion.topic:id,title,slug'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'discussion_id', 'body', 'created_at']);

        return Inertia::render('users/show', [
            'profileUser' => $profileData,
            'discussions' => $discussions,
            'recentReplies' => $recentReplies,
        ]);
    }
}
