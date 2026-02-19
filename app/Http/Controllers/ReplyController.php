<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReplyRequest;
use App\Http\Requests\UpdateReplyRequest;
use App\Models\Reply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReplyController extends Controller
{
    /**
     * Store a newly created reply.
     */
    public function store(StoreReplyRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        // Calculate depth from parent
        if (! empty($data['parent_id'])) {
            $parent = Reply::findOrFail($data['parent_id']);
            $depth = $parent->depth + 1;

            if ($depth > 2) {
                abort(422, 'Maximum reply depth exceeded.');
            }

            $data['depth'] = $depth;
        } else {
            $data['depth'] = 0;
        }

        $reply = Reply::create($data);

        $discussion = $reply->discussion;

        return back();
    }

    /**
     * Update the specified reply.
     */
    public function update(UpdateReplyRequest $request, Reply $reply): RedirectResponse
    {
        $reply->update($request->validated());

        return back();
    }

    /**
     * Remove the specified reply.
     */
    public function destroy(Reply $reply): RedirectResponse
    {
        Gate::authorize('delete', $reply);

        $reply->delete();

        return back();
    }
}
