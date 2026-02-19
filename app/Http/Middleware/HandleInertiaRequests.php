<?php

namespace App\Http\Middleware;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'unread_messages_count' => $request->user() ? $this->getUnreadMessagesCount($request->user()) : 0,
        ];
    }

    private function getUnreadMessagesCount(mixed $user): int
    {
        return Conversation::query()
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->whereHas('messages', function ($q) use ($user) {
                $q->where('messages.created_at', '>', function ($sub) use ($user) {
                    $sub->select('last_read_at')
                        ->from('conversation_participants')
                        ->whereColumn('conversation_participants.conversation_id', 'conversations.id')
                        ->where('conversation_participants.user_id', $user->id)
                        ->limit(1);
                })->orWhereExists(function ($sub) use ($user) {
                    $sub->selectRaw('1')
                        ->from('conversation_participants')
                        ->whereColumn('conversation_participants.conversation_id', 'conversations.id')
                        ->where('conversation_participants.user_id', $user->id)
                        ->whereNull('conversation_participants.last_read_at');
                });
            })
            ->count();
    }
}
