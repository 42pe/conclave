<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConversationRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Services\PostHogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    public function __construct(private PostHogService $postHog) {}

    /**
     * List conversations for the authenticated user.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->with([
                'participants' => fn ($q) => $q->select('users.id', 'users.name', 'users.username', 'users.avatar_path', 'users.is_deleted', 'users.preferred_name'),
                'latestMessage.user:id,name,username,is_deleted,preferred_name',
            ])
            ->withMax('messages', 'created_at')
            ->orderByDesc('messages_max_created_at')
            ->paginate(20);

        // Add unread flag per conversation
        $conversations->getCollection()->transform(function ($conversation) use ($user) {
            $participant = $conversation->participants->find($user->id);
            $lastReadAt = $participant?->pivot?->last_read_at;
            $conversation->has_unread = $conversation->latestMessage &&
                (! $lastReadAt || $conversation->latestMessage->created_at->gt($lastReadAt));

            return $conversation;
        });

        return Inertia::render('messages/index', [
            'conversations' => $conversations,
        ]);
    }

    /**
     * Show a conversation.
     */
    public function show(Conversation $conversation, Request $request): Response
    {
        Gate::authorize('view', $conversation);

        $user = $request->user();

        $conversation->load([
            'participants' => fn ($q) => $q->select('users.id', 'users.name', 'users.username', 'users.avatar_path', 'users.is_deleted', 'users.preferred_name'),
        ]);

        $messages = $conversation->messages()
            ->with(['user:id,name,username,avatar_path,is_deleted,preferred_name'])
            ->oldest()
            ->paginate(50);

        // Mark as read
        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);

        return Inertia::render('messages/show', [
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    /**
     * Start a new conversation (or redirect to existing).
     */
    public function store(StoreConversationRequest $request): RedirectResponse
    {
        $user = $request->user();
        $recipientId = $request->validated()['recipient_id'];

        // Check for existing conversation
        $conversation = Conversation::between($user->id, $recipientId);

        if (! $conversation) {
            $conversation = Conversation::create();
            $conversation->participants()->attach([$user->id, $recipientId]);
        }

        // Create the first message
        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'body' => $request->validated()['body'],
        ]);

        $this->postHog->capture($user, 'conversation_started', [
            'conversation_id' => $conversation->id,
        ]);

        // Notify the recipient
        $recipient = User::find($recipientId);
        if ($recipient && ! $recipient->is_deleted) {
            $recipient->notify(new NewMessageNotification($conversation, $user));
        }

        return to_route('conversations.show', $conversation);
    }
}
