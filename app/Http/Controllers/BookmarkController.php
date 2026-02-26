<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Discussion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookmarkController extends Controller
{
    /**
     * Toggle a bookmark on a discussion.
     */
    public function toggle(Request $request, Discussion $discussion): JsonResponse
    {
        $existing = Bookmark::query()
            ->where('user_id', $request->user()->id)
            ->where('discussion_id', $discussion->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json(['bookmarked' => false]);
        }

        Bookmark::create([
            'user_id' => $request->user()->id,
            'discussion_id' => $discussion->id,
        ]);

        return response()->json(['bookmarked' => true]);
    }

    /**
     * Display a listing of the user's bookmarked discussions.
     */
    public function index(Request $request): Response
    {
        $bookmarks = Bookmark::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'discussion:id,title,slug,topic_id,reply_count,last_reply_at,created_at',
                'discussion.topic:id,title,slug,icon',
            ])
            ->orderByDesc('created_at')
            ->paginate(15);

        return Inertia::render('bookmarks/index', [
            'bookmarks' => $bookmarks,
        ]);
    }
}
