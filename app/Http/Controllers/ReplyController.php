<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReplyRequest;
use App\Http\Requests\UpdateReplyRequest;
use App\Models\Discussion;
use App\Models\Reply;
use App\Models\User;
use App\Notifications\NewReplyNotification;
use App\Services\MentionService;
use App\Services\PostHogService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class ReplyController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private PostHogService $postHog,
        private MentionService $mentions,
    ) {}

    /**
     * Store a newly created reply.
     */
    public function store(StoreReplyRequest $request, Discussion $discussion): RedirectResponse
    {
        $this->authorize('create', [Reply::class, $discussion]);

        $depth = 0;
        if ($request->parent_id) {
            $parent = Reply::findOrFail($request->parent_id);
            $depth = $parent->depth + 1;
        }

        $reply = Reply::create([
            'discussion_id' => $discussion->id,
            'user_id' => $request->user()->id,
            'parent_id' => $request->parent_id,
            'depth' => $depth,
            'body' => $request->body,
        ]);

        $this->postHog->capture((string) $request->user()->id, 'reply_created', [
            'reply_id' => $reply->id,
            'discussion_id' => $discussion->id,
        ]);

        $this->sendReplyNotifications($reply, $discussion, $request->user());

        $this->mentions->notifyMentionedUsers(
            $request->validated('body'),
            $request->user(),
            $discussion,
            $reply,
        );

        return back();
    }

    /**
     * Update the specified reply.
     */
    public function update(UpdateReplyRequest $request, Reply $reply): RedirectResponse
    {
        $this->authorize('update', $reply);

        $reply->update([
            'body' => $request->body,
        ]);

        return back();
    }

    /**
     * Remove the specified reply.
     */
    public function destroy(Reply $reply): RedirectResponse
    {
        $this->authorize('delete', $reply);

        $reply->delete();

        return back();
    }

    /**
     * Send notifications to discussion author and parent reply author.
     */
    private function sendReplyNotifications(Reply $reply, Discussion $discussion, User $actor): void
    {
        $notified = [];

        // Notify discussion author (if not the actor)
        $discussionAuthor = $discussion->user;
        if ($discussionAuthor && $discussionAuthor->id !== $actor->id) {
            $discussionAuthor->notify(new NewReplyNotification($reply, $discussion));
            $notified[] = $discussionAuthor->id;
        }

        // Notify parent reply author (if nested and not already notified, and not the actor)
        if ($reply->parent_id) {
            $parentAuthor = $reply->parent?->user;
            if ($parentAuthor && $parentAuthor->id !== $actor->id && ! in_array($parentAuthor->id, $notified)) {
                $parentAuthor->notify(new NewReplyNotification($reply, $discussion));
            }
        }
    }
}
