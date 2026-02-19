<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Determine whether the user can view the conversation.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can send a message in the conversation.
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can start a new conversation.
     */
    public function create(User $user): bool
    {
        return ! $user->is_deleted && ! $user->is_suspended;
    }
}
