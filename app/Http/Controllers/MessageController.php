<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;

class MessageController extends Controller
{
    /**
     * Store a new message in a conversation.
     */
    public function store(StoreMessageRequest $request): RedirectResponse
    {
        Message::create([
            'conversation_id' => $request->validated()['conversation_id'],
            'user_id' => $request->user()->id,
            'body' => $request->validated()['body'],
        ]);

        return back();
    }
}
