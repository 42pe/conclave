<?php

namespace App\Observers;

use App\Models\Reply;

class ReplyObserver
{
    /**
     * Handle the Reply "created" event.
     */
    public function created(Reply $reply): void
    {
        $reply->discussion()->update([
            'reply_count' => $reply->discussion->replies()->count(),
            'last_reply_at' => $reply->created_at,
        ]);
    }

    /**
     * Handle the Reply "deleted" event.
     */
    public function deleted(Reply $reply): void
    {
        $discussion = $reply->discussion()->first();

        if ($discussion) {
            $discussion->reply_count = $discussion->replies()->count();
            $discussion->last_reply_at = $discussion->replies()->latest()->value('created_at');
            $discussion->save();
        }
    }
}
