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
        $discussion = $reply->discussion()->first();

        $discussion->increment('reply_count');
        $discussion->update(['last_reply_at' => now()]);
    }

    /**
     * Handle the Reply "deleted" event.
     */
    public function deleted(Reply $reply): void
    {
        $discussion = $reply->discussion()->first();

        if ($discussion) {
            $discussion->decrement('reply_count');
        }
    }
}
