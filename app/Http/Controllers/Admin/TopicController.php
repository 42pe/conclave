<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTopicRequest;
use App\Http\Requests\Admin\UpdateTopicRequest;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
                ->with('creator:id,name')
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
        $nextSortOrder = (int) Topic::query()->max('sort_order') + 1;

        return Inertia::render('admin/topics/create', [
            'nextSortOrder' => $nextSortOrder,
        ]);
    }

    /**
     * Store a newly created topic.
     */
    public function store(StoreTopicRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('header_image');
        $data['created_by'] = $request->user()->id;

        if ($request->hasFile('header_image')) {
            $file = $request->file('header_image');
            $data['header_image_path'] = $file->storeAs(
                'topics/headers',
                Str::uuid().'.'.$file->getClientOriginalExtension(),
                'public',
            );
        }

        Topic::create($data);

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
        $data = $request->safe()->except('header_image');

        if ($request->hasFile('header_image')) {
            if ($topic->header_image_path && Storage::disk('public')->exists($topic->header_image_path)) {
                Storage::disk('public')->delete($topic->header_image_path);
            }

            $file = $request->file('header_image');
            $data['header_image_path'] = $file->storeAs(
                'topics/headers',
                Str::uuid().'.'.$file->getClientOriginalExtension(),
                'public',
            );
        }

        $topic->update($data);

        return to_route('admin.topics.index');
    }

    /**
     * Remove the specified topic.
     */
    public function destroy(Topic $topic): RedirectResponse
    {
        if ($topic->header_image_path && Storage::disk('public')->exists($topic->header_image_path)) {
            Storage::disk('public')->delete($topic->header_image_path);
        }

        $topic->delete();

        return to_route('admin.topics.index');
    }
}
