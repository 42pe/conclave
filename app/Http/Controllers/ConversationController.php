<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConversationRequest;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the user's conversations.
     */
    public function index(): Response
    {
        $user = request()->user();

        $conversations = Conversation::query()
            ->forUser($user)
            ->with([
                'users:id,name,username,avatar_path,preferred_name,is_deleted',
                'latestMessage.user:id,name,username,preferred_name,is_deleted',
            ])
            ->withMax('messages', 'created_at')
            ->orderByDesc('messages_max_created_at')
            ->get()
            ->map(function (Conversation $conversation) use ($user) {
                $participant = $conversation->participants()
                    ->where('user_id', $user->id)
                    ->first();

                $unreadCount = $conversation->messages()
                    ->when($participant?->last_read_at, fn ($q, $lastRead) => $q->where('created_at', '>', $lastRead))
                    ->when(! $participant?->last_read_at, fn ($q) => $q)
                    ->where('user_id', '!=', $user->id)
                    ->count();

                $conversation->setAttribute('unread_count', $unreadCount);

                return $conversation;
            });

        return Inertia::render('messages/index', [
            'conversations' => $conversations,
        ]);
    }

    /**
     * Display the specified conversation.
     */
    public function show(Conversation $conversation): Response
    {
        $this->authorize('view', $conversation);

        $user = request()->user();

        // Mark as read
        $conversation->participants()
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        $conversation->load([
            'users:id,name,username,avatar_path,preferred_name,is_deleted',
        ]);

        $messages = $conversation->messages()
            ->with('user:id,name,username,avatar_path,preferred_name,is_deleted')
            ->orderBy('created_at')
            ->get();

        return Inertia::render('messages/show', [
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    /**
     * Store a new conversation (or redirect to existing one).
     */
    public function store(StoreConversationRequest $request): RedirectResponse
    {
        $this->authorize('create', Conversation::class);

        $user = $request->user();
        $recipient = User::findOrFail($request->recipient_id);

        // Check for existing conversation
        $conversation = Conversation::between($user, $recipient);

        if (! $conversation) {
            $conversation = Conversation::create();
            $conversation->participants()->createMany([
                ['user_id' => $user->id, 'last_read_at' => now()],
                ['user_id' => $recipient->id],
            ]);
        }

        // Create the message
        $conversation->messages()->create([
            'user_id' => $user->id,
            'body' => $request->body,
        ]);

        // Update sender's last_read_at
        $conversation->participants()
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        return to_route('conversations.show', $conversation);
    }
}
