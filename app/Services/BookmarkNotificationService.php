<?php

namespace App\Services;

use App\Models\Discussion;
use App\Models\Reply;
use App\Models\User;
use App\Notifications\BookmarkActivityNotification;

class BookmarkNotificationService
{
    /**
     * Notify users who bookmarked a discussion about new activity.
     *
     * @param  array<int, int>  $alreadyNotifiedUserIds
     */
    public function notifyBookmarkingUsers(Reply $reply, Discussion $discussion, User $replyAuthor, array $alreadyNotifiedUserIds = []): void
    {
        $bookmarkingUsers = User::query()
            ->whereHas('bookmarks', fn ($q) => $q->where('discussion_id', $discussion->id))
            ->where('id', '!=', $replyAuthor->id)
            ->whereNotIn('id', $alreadyNotifiedUserIds)
            ->where('is_deleted', false)
            ->get();

        foreach ($bookmarkingUsers as $user) {
            $user->notify(new BookmarkActivityNotification($reply, $discussion));
        }
    }
}
