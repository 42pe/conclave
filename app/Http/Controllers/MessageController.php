<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class MessageController extends Controller
{
    use AuthorizesRequests;

    /**
     * Store a new message in the conversation.
     */
    public function store(StoreMessageRequest $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('sendMessage', $conversation);

        $conversation->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        // Update sender's last_read_at
        $conversation->participants()
            ->where('user_id', $request->user()->id)
            ->update(['last_read_at' => now()]);

        return back();
    }
}
