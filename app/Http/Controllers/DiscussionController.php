<?php

namespace App\Http\Controllers;

use App\Enums\TopicVisibility;
use App\Http\Requests\StoreDiscussionRequest;
use App\Http\Requests\UpdateDiscussionRequest;
use App\Models\Bookmark;
use App\Models\Discussion;
use App\Models\Location;
use App\Models\Reply;
use App\Models\Topic;
use App\Services\MentionService;
use App\Services\PostHogService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DiscussionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private PostHogService $postHog,
        private MentionService $mentions,
    ) {}

    /**
     * Display a listing of discussions for a topic.
     */
    public function index(Topic $topic): Response|RedirectResponse
    {
        if (! request()->user() && $topic->visibility !== TopicVisibility::Public) {
            return redirect()->guest(route('login'));
        }

        $this->authorize('viewAny', [Discussion::class, $topic]);

        $discussions = $topic->discussions()
            ->with(['user:id,name,username,avatar_path,preferred_name,is_deleted', 'location:id,name'])
            ->withCount('likes')
            ->when(request('location'), fn ($q, $locationId) => $q->byLocation((int) $locationId))
            ->orderByDesc('is_pinned')
            ->orderByDesc('last_reply_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        $user = request()->user();

        if ($user) {
            $discussionIds = $discussions->getCollection()->pluck('id')->all();

            $likedIds = $user->likes()
                ->where('likeable_type', Discussion::class)
                ->whereIn('likeable_id', $discussionIds)
                ->pluck('likeable_id')
                ->all();

            $bookmarkedIds = Bookmark::query()
                ->where('user_id', $user->id)
                ->whereIn('discussion_id', $discussionIds)
                ->pluck('discussion_id')
                ->all();

            $discussions->getCollection()->each(function ($discussion) use ($likedIds, $bookmarkedIds) {
                $discussion->setAttribute('user_has_liked', in_array($discussion->id, $likedIds));
                $discussion->setAttribute('user_has_bookmarked', in_array($discussion->id, $bookmarkedIds));
            });
        } else {
            $discussions->getCollection()->each(function ($discussion) {
                $discussion->setAttribute('user_has_liked', false);
                $discussion->setAttribute('user_has_bookmarked', false);
            });
        }

        return Inertia::render('topics/show', [
            'topic' => $topic,
            'discussions' => $discussions,
            'locations' => Location::query()->active()->orderBy('sort_order')->get(['id', 'name']),
            'can' => [
                'create' => $user
                    ? $user->can('create', [Discussion::class, $topic])
                    : false,
            ],
            'authUserId' => $user?->id,
        ]);
    }

    /**
     * Display the specified discussion.
     */
    public function show(Topic $topic, Discussion $discussion): Response|RedirectResponse
    {
        if (! request()->user() && $topic->visibility !== TopicVisibility::Public) {
            return redirect()->guest(route('login'));
        }

        $this->authorize('view', $discussion);

        $discussion->increment('view_count');

        $discussion->load([
            'user:id,name,username,avatar_path,preferred_name,is_deleted,bio,role',
            'location:id,name',
        ]);
        $discussion->loadCount('likes');

        $user = request()->user();

        $discussion->setAttribute(
            'user_has_liked',
            $user ? $discussion->likes()->where('user_id', $user->id)->exists() : false,
        );

        $discussion->setAttribute(
            'user_has_bookmarked',
            $user ? $discussion->bookmarks()->where('user_id', $user->id)->exists() : false,
        );

        $replies = $discussion->replies()
            ->whereNull('parent_id')
            ->withCount('likes')
            ->with([
                'user:id,name,username,avatar_path,preferred_name,is_deleted,role',
                'children' => fn ($q) => $q->withCount('likes')->with([
                    'user:id,name,username,avatar_path,preferred_name,is_deleted,role',
                    'children' => fn ($q) => $q->withCount('likes')->with(
                        'user:id,name,username,avatar_path,preferred_name,is_deleted,role',
                    )->orderBy('created_at'),
                ])->orderBy('created_at'),
            ])
            ->orderBy('created_at')
            ->get();

        if ($user) {
            $this->postHog->capture((string) $user->id, 'discussion_viewed', [
                'discussion_id' => $discussion->id,
                'topic_id' => $topic->id,
            ]);

            $likedReplyIds = $user->likes()
                ->where('likeable_type', Reply::class)
                ->whereIn('likeable_id', $this->getAllReplyIds($replies))
                ->pluck('likeable_id')
                ->all();

            $this->setUserHasLikedOnReplies($replies, $likedReplyIds);
        } else {
            $this->setUserHasLikedOnReplies($replies, []);
        }

        return Inertia::render('discussions/show', [
            'topic' => $topic,
            'discussion' => $discussion,
            'replies' => $replies,
            'can' => [
                'update' => $user
                    ? $user->can('update', $discussion)
                    : false,
                'delete' => $user
                    ? $user->can('delete', $discussion)
                    : false,
                'reply' => $user
                    ? $user->can('create', [Reply::class, $discussion])
                    : false,
            ],
        ]);
    }

    /**
     * Get all reply IDs from a nested reply collection.
     *
     * @param  \Illuminate\Support\Collection<int, Reply>  $replies
     * @return list<int>
     */
    private function getAllReplyIds($replies): array
    {
        $ids = [];

        foreach ($replies as $reply) {
            $ids[] = $reply->id;
            if ($reply->relationLoaded('children') && $reply->children->isNotEmpty()) {
                $ids = array_merge($ids, $this->getAllReplyIds($reply->children));
            }
        }

        return $ids;
    }

    /**
     * Set user_has_liked attribute on nested replies.
     *
     * @param  \Illuminate\Support\Collection<int, Reply>  $replies
     * @param  list<int>  $likedReplyIds
     */
    private function setUserHasLikedOnReplies($replies, array $likedReplyIds): void
    {
        foreach ($replies as $reply) {
            $reply->setAttribute('user_has_liked', in_array($reply->id, $likedReplyIds));
            if ($reply->relationLoaded('children') && $reply->children->isNotEmpty()) {
                $this->setUserHasLikedOnReplies($reply->children, $likedReplyIds);
            }
        }
    }

    /**
     * Show the form for creating a new discussion.
     */
    public function create(Topic $topic): Response
    {
        $this->authorize('create', [Discussion::class, $topic]);

        return Inertia::render('discussions/create', [
            'topic' => $topic,
            'locations' => Location::query()->active()->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created discussion.
     */
    public function store(StoreDiscussionRequest $request, Topic $topic): RedirectResponse
    {
        $this->authorize('create', [Discussion::class, $topic]);

        $validated = $request->validated();
        $body = is_string($validated['body']) ? json_decode($validated['body'], true) : $validated['body'];
        $validated['body'] = $body;

        $discussion = Discussion::create([
            ...$validated,
            'topic_id' => $topic->id,
            'user_id' => $request->user()->id,
        ]);

        $this->postHog->capture((string) $request->user()->id, 'discussion_created', [
            'discussion_id' => $discussion->id,
            'topic_id' => $topic->id,
        ]);

        $this->mentions->notifyMentionedUsers(
            $body,
            $request->user(),
            $discussion,
        );

        return to_route('topics.discussions.show', [$topic, $discussion]);
    }

    /**
     * Show the form for editing the specified discussion.
     */
    public function edit(Topic $topic, Discussion $discussion): Response
    {
        $this->authorize('update', $discussion);

        return Inertia::render('discussions/edit', [
            'topic' => $topic,
            'discussion' => $discussion,
            'locations' => Location::query()->active()->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified discussion.
     */
    public function update(UpdateDiscussionRequest $request, Topic $topic, Discussion $discussion): RedirectResponse
    {
        $this->authorize('update', $discussion);

        $validated = $request->validated();
        $body = is_string($validated['body']) ? json_decode($validated['body'], true) : $validated['body'];
        $validated['body'] = $body;

        $discussion->update($validated);

        $this->mentions->notifyMentionedUsers(
            $body,
            $request->user(),
            $discussion,
        );

        return to_route('topics.discussions.show', [$topic, $discussion]);
    }

    /**
     * Remove the specified discussion.
     */
    public function destroy(Topic $topic, Discussion $discussion): RedirectResponse
    {
        $this->authorize('delete', $discussion);

        $discussion->delete();

        return to_route('topics.show', $topic);
    }
}
