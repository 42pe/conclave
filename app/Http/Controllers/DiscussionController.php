<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiscussionRequest;
use App\Http\Requests\UpdateDiscussionRequest;
use App\Models\Discussion;
use App\Models\Location;
use App\Models\Topic;
use App\Services\PostHogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DiscussionController extends Controller
{
    public function __construct(private PostHogService $postHog) {}

    /**
     * Display discussions for a topic.
     */
    public function index(Topic $topic): Response
    {
        Gate::authorize('viewAny', [Discussion::class, $topic]);

        $discussions = $topic->discussions()
            ->with(['user:id,name,username,avatar_path,is_deleted,preferred_name', 'location:id,name'])
            ->orderByDesc('is_pinned')
            ->latest('updated_at')
            ->paginate(20);

        return Inertia::render('topics/show', [
            'topic' => $topic,
            'discussions' => $discussions,
            'locations' => Location::query()->active()->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    /**
     * Show a single discussion.
     */
    public function show(Topic $topic, Discussion $discussion): Response
    {
        Gate::authorize('view', $discussion);

        $discussion->load([
            'user:id,name,username,avatar_path,is_deleted,preferred_name',
            'location:id,name',
        ]);

        $replies = $discussion->replies()
            ->with(['user:id,name,username,avatar_path,is_deleted,preferred_name'])
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->with(['user:id,name,username,avatar_path,is_deleted,preferred_name'])
                    ->with(['children' => function ($q2) {
                        $q2->with(['user:id,name,username,avatar_path,is_deleted,preferred_name'])
                            ->oldest();
                    }])
                    ->oldest();
            }])
            ->oldest()
            ->get();

        $user = request()->user();

        if ($user) {
            $this->postHog->capture($user, 'discussion_viewed', [
                'discussion_id' => $discussion->id,
                'topic_id' => $topic->id,
            ]);
        }

        return Inertia::render('discussions/show', [
            'topic' => $topic,
            'discussion' => $discussion,
            'replies' => $replies,
            'canEdit' => $user?->can('update', $discussion) ?? false,
            'canDelete' => $user?->can('delete', $discussion) ?? false,
            'canReply' => $user?->can('create', [\App\Models\Reply::class, $discussion]) ?? false,
        ]);
    }

    /**
     * Show the form for creating a new discussion.
     */
    public function create(Topic $topic): Response
    {
        Gate::authorize('create', [Discussion::class, $topic]);

        return Inertia::render('discussions/create', [
            'topic' => $topic,
            'locations' => Location::query()->active()->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created discussion.
     */
    public function store(StoreDiscussionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $discussion = Discussion::create($data);

        $this->postHog->capture($request->user(), 'discussion_created', [
            'discussion_id' => $discussion->id,
            'topic_id' => $discussion->topic_id,
        ]);

        return to_route('discussions.show', [$discussion->topic, $discussion]);
    }

    /**
     * Show the form for editing the discussion.
     */
    public function edit(Topic $topic, Discussion $discussion): Response
    {
        Gate::authorize('update', $discussion);

        return Inertia::render('discussions/edit', [
            'topic' => $topic,
            'discussion' => $discussion,
        ]);
    }

    /**
     * Update the specified discussion.
     */
    public function update(UpdateDiscussionRequest $request, Topic $topic, Discussion $discussion): RedirectResponse
    {
        $discussion->update($request->validated());

        return to_route('discussions.show', [$topic, $discussion]);
    }

    /**
     * Remove the specified discussion.
     */
    public function destroy(Topic $topic, Discussion $discussion): RedirectResponse
    {
        Gate::authorize('delete', $discussion);

        $discussion->delete();

        return to_route('topics.show', $topic);
    }
}
