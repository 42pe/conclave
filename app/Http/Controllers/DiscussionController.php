<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiscussionRequest;
use App\Http\Requests\UpdateDiscussionRequest;
use App\Models\Discussion;
use App\Models\Location;
use App\Models\Reply;
use App\Models\Topic;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DiscussionController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of discussions for a topic.
     */
    public function index(Topic $topic): Response
    {
        $this->authorize('viewAny', [Discussion::class, $topic]);

        $discussions = $topic->discussions()
            ->with(['user:id,name,username,avatar_path,preferred_name,is_deleted', 'location:id,name'])
            ->when(request('location'), fn ($q, $locationId) => $q->byLocation((int) $locationId))
            ->orderByDesc('is_pinned')
            ->orderByDesc('last_reply_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('topics/show', [
            'topic' => $topic,
            'discussions' => $discussions,
            'locations' => Location::query()->active()->orderBy('sort_order')->get(['id', 'name']),
            'can' => [
                'create' => request()->user()
                    ? request()->user()->can('create', [Discussion::class, $topic])
                    : false,
            ],
        ]);
    }

    /**
     * Display the specified discussion.
     */
    public function show(Topic $topic, Discussion $discussion): Response
    {
        $this->authorize('view', $discussion);

        $discussion->load([
            'user:id,name,username,avatar_path,preferred_name,is_deleted,bio,role',
            'location:id,name',
        ]);

        $replies = $discussion->replies()
            ->whereNull('parent_id')
            ->with([
                'user:id,name,username,avatar_path,preferred_name,is_deleted,role',
                'children' => fn ($q) => $q->with([
                    'user:id,name,username,avatar_path,preferred_name,is_deleted,role',
                    'children' => fn ($q) => $q->with(
                        'user:id,name,username,avatar_path,preferred_name,is_deleted,role',
                    )->orderBy('created_at'),
                ])->orderBy('created_at'),
            ])
            ->orderBy('created_at')
            ->get();

        return Inertia::render('discussions/show', [
            'topic' => $topic,
            'discussion' => $discussion,
            'replies' => $replies,
            'can' => [
                'update' => request()->user()
                    ? request()->user()->can('update', $discussion)
                    : false,
                'delete' => request()->user()
                    ? request()->user()->can('delete', $discussion)
                    : false,
                'reply' => request()->user()
                    ? request()->user()->can('create', [Reply::class, $discussion])
                    : false,
            ],
        ]);
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

        $discussion = Discussion::create([
            ...$request->validated(),
            'topic_id' => $topic->id,
            'user_id' => $request->user()->id,
        ]);

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

        $discussion->update($request->validated());

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
