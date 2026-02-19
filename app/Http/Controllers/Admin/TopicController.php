<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTopicRequest;
use App\Http\Requests\Admin\UpdateTopicRequest;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TopicController extends Controller
{
    /**
     * Display a listing of topics.
     */
    public function index(): Response
    {
        return Inertia::render('admin/topics/index', [
            'topics' => Topic::query()
                ->with('creator:id,name,username')
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new topic.
     */
    public function create(): Response
    {
        return Inertia::render('admin/topics/create');
    }

    /**
     * Store a newly created topic.
     */
    public function store(StoreTopicRequest $request): RedirectResponse
    {
        Topic::create([
            ...$request->validated(),
            'sort_order' => $request->validated('sort_order', 0),
            'created_by' => $request->user()->id,
        ]);

        return to_route('admin.topics.index');
    }

    /**
     * Show the form for editing the specified topic.
     */
    public function edit(Topic $topic): Response
    {
        return Inertia::render('admin/topics/edit', [
            'topic' => $topic,
        ]);
    }

    /**
     * Update the specified topic.
     */
    public function update(UpdateTopicRequest $request, Topic $topic): RedirectResponse
    {
        $topic->update([
            ...$request->validated(),
            'sort_order' => $request->validated('sort_order', 0),
        ]);

        return to_route('admin.topics.index');
    }

    /**
     * Remove the specified topic.
     */
    public function destroy(Topic $topic): RedirectResponse
    {
        $topic->delete();

        return to_route('admin.topics.index');
    }
}
