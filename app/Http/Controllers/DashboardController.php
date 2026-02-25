<?php

namespace App\Http\Controllers;

use App\Enums\TopicVisibility;
use App\Models\Discussion;
use App\Models\Reply;
use App\Models\Topic;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the user dashboard.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('dashboard', [
            'userStats' => [
                'discussions_count' => Discussion::query()->where('user_id', $user->id)->count(),
                'replies_count' => Reply::query()->where('user_id', $user->id)->count(),
            ],
            'recentReplies' => Inertia::defer(function () use ($user) {
                return Reply::query()
                    ->whereHas('discussion', fn ($q) => $q->where('user_id', $user->id))
                    ->where('replies.user_id', '!=', $user->id)
                    ->with([
                        'user:id,name,username,avatar_path,preferred_name,is_deleted',
                        'discussion:id,title,slug,topic_id',
                        'discussion.topic:id,title,slug,icon',
                    ])
                    ->latest()
                    ->limit(5)
                    ->get();
            }),
            'activeTopics' => Inertia::defer(function () use ($user) {
                return Topic::query()
                    ->withCount('discussions')
                    ->when(! $user->isAdminOrModerator(), fn ($q) => $q->whereIn('visibility', [
                        TopicVisibility::Public,
                        TopicVisibility::Private,
                    ]))
                    ->orderByDesc(
                        Discussion::query()
                            ->selectRaw('MAX(discussions.updated_at)')
                            ->whereColumn('discussions.topic_id', 'topics.id')
                    )
                    ->limit(5)
                    ->get(['id', 'title', 'slug', 'icon']);
            }),
            'recentDiscussions' => Inertia::defer(function () use ($user) {
                return Discussion::query()
                    ->with([
                        'user:id,name,username,avatar_path,preferred_name,is_deleted',
                        'topic:id,title,slug,icon',
                    ])
                    ->whereHas('topic', fn ($q) => $q->when(
                        ! $user->isAdminOrModerator(),
                        fn ($q2) => $q2->whereIn('visibility', [
                            TopicVisibility::Public,
                            TopicVisibility::Private,
                        ])
                    ))
                    ->latest('updated_at')
                    ->limit(5)
                    ->get();
            }),
        ]);
    }
}
