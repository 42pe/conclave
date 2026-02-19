<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Notifications\NewMessageNotification;
use App\Services\PostHogService;
use Illuminate\Http\RedirectResponse;

class MessageController extends Controller
{
    public function __construct(private PostHogService $postHog) {}

    /**
     * Store a new message in a conversation.
     */
    public function store(StoreMessageRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        Message::create([
            'conversation_id' => $validated['conversation_id'],
            'user_id' => $user->id,
            'body' => $validated['body'],
        ]);

        $this->postHog->capture($user, 'message_sent', [
            'conversation_id' => (int) $validated['conversation_id'],
        ]);

        // Notify other participants
        $conversation = Conversation::with('participants')->find($validated['conversation_id']);
        $conversation->participants
            ->where('id', '!=', $user->id)
            ->filter(fn ($participant) => ! $participant->is_deleted)
            ->each(fn ($participant) => $participant->notify(new NewMessageNotification($conversation, $user)));

        return back();
    }
}
