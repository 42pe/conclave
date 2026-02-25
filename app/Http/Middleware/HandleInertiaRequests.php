<?php

namespace App\Http\Middleware;

use App\Models\ConversationParticipant;
use App\Models\User;
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
            'unread_messages_count' => fn () => $request->user()
                ? $this->getUnreadMessagesCount($request->user())
                : 0,
            'unread_notifications_count' => fn () => $request->user()
                ? $request->user()->unreadNotifications()->count()
                : 0,
        ];
    }

    /**
     * Get the count of conversations with unread messages for the user.
     */
    private function getUnreadMessagesCount(User $user): int
    {
        return ConversationParticipant::query()
            ->where('user_id', $user->id)
            ->whereHas('conversation.messages', function ($q) use ($user) {
                $q->where('user_id', '!=', $user->id);
            })
            ->where(function ($q) use ($user) {
                $q->whereNull('last_read_at')
                    ->orWhereHas('conversation.messages', function ($q2) use ($user) {
                        $q2->where('user_id', '!=', $user->id)
                            ->whereColumn('messages.created_at', '>', 'conversation_participants.last_read_at');
                    });
            })
            ->count();
    }
}
