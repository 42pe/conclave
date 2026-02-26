<?php

namespace App\Http\Controllers;

use App\Models\Discussion;
use App\Models\Reply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Toggle a like on a discussion.
     */
    public function toggleDiscussionLike(Request $request, Discussion $discussion): JsonResponse
    {
        $existing = $discussion->likes()->where('user_id', $request->user()->id)->first();

        if ($existing) {
            $existing->delete();

            return response()->json([
                'liked' => false,
                'likes_count' => $discussion->likes()->count(),
            ]);
        }

        $discussion->likes()->create(['user_id' => $request->user()->id]);

        return response()->json([
            'liked' => true,
            'likes_count' => $discussion->likes()->count(),
        ]);
    }

    /**
     * Toggle a like on a reply.
     */
    public function toggleReplyLike(Request $request, Reply $reply): JsonResponse
    {
        $existing = $reply->likes()->where('user_id', $request->user()->id)->first();

        if ($existing) {
            $existing->delete();

            return response()->json([
                'liked' => false,
                'likes_count' => $reply->likes()->count(),
            ]);
        }

        $reply->likes()->create(['user_id' => $request->user()->id]);

        return response()->json([
            'liked' => true,
            'likes_count' => $reply->likes()->count(),
        ]);
    }
}
