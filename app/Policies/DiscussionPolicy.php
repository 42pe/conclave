<?php

namespace App\Policies;

use App\Enums\TopicVisibility;
use App\Models\Discussion;
use App\Models\Topic;
use App\Models\User;

class DiscussionPolicy
{
    /**
     * Determine whether the user can view discussions in a topic.
     */
    public function viewAny(?User $user, Topic $topic): bool
    {
        return match ($topic->visibility) {
            TopicVisibility::Public => true,
            TopicVisibility::Private => $user !== null,
            TopicVisibility::Restricted => $user?->isAdminOrModerator() ?? false,
        };
    }

    /**
     * Determine whether the user can view the discussion.
     */
    public function view(?User $user, Discussion $discussion): bool
    {
        return $this->viewAny($user, $discussion->topic);
    }

    /**
     * Determine whether the user can create discussions in a topic.
     */
    public function create(User $user, Topic $topic): bool
    {
        if ($user->is_suspended) {
            return false;
        }

        return $this->viewAny($user, $topic);
    }

    /**
     * Determine whether the user can update the discussion.
     */
    public function update(User $user, Discussion $discussion): bool
    {
        if ($user->is_suspended) {
            return false;
        }

        if ($user->isAdminOrModerator()) {
            return true;
        }

        return $user->id === $discussion->user_id;
    }

    /**
     * Determine whether the user can delete the discussion.
     */
    public function delete(User $user, Discussion $discussion): bool
    {
        if ($user->isAdminOrModerator()) {
            return true;
        }

        return $user->id === $discussion->user_id;
    }
}
