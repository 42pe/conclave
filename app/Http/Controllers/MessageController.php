<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use App\Notifications\NewMessageNotification;
use App\Services\PostHogService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class MessageController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private PostHogService $postHog,
    ) {}

    /**
     * Store a new message in the conversation.
     */
    public function store(StoreMessageRequest $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('sendMessage', $conversation);

        $message = $conversation->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        // Update sender's last_read_at
        $conversation->participants()
            ->where('user_id', $request->user()->id)
            ->update(['last_read_at' => now()]);

        $this->postHog->capture((string) $request->user()->id, 'message_sent', [
            'conversation_id' => $conversation->id,
        ]);

        // Notify other participants
        $conversation->users()
            ->where('users.id', '!=', $request->user()->id)
            ->each(fn ($user) => $user->notify(new NewMessageNotification($message, $conversation)));

        return back();
    }
}
