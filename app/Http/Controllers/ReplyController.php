<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReplyRequest;
use App\Http\Requests\UpdateReplyRequest;
use App\Models\Discussion;
use App\Models\Reply;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class ReplyController extends Controller
{
    use AuthorizesRequests;

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

        Reply::create([
            'discussion_id' => $discussion->id,
            'user_id' => $request->user()->id,
            'parent_id' => $request->parent_id,
            'depth' => $depth,
            'body' => $request->body,
        ]);

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
}
