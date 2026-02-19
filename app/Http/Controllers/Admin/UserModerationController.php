<?php

namespace App\Http\Controllers\Admin;

use App\Actions\BanUser;
use App\Actions\DeleteUser;
use App\Actions\SuspendUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BanUserRequest;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Models\User;
use App\Services\PostHogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserModerationController extends Controller
{
    public function __construct(private PostHogService $postHog) {}

    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search', '');

        $users = User::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        return Inertia::render('admin/users/create');
    }

    /**
     * Store a newly created user.
     */
    public function store(CreateUserRequest $request): RedirectResponse
    {
        $newUser = User::create($request->validated());

        $this->postHog->capture($request->user(), 'user_created_by_admin', [
            'new_user_id' => $newUser->id,
        ]);

        return to_route('admin.users.index');
    }

    /**
     * Suspend a user.
     */
    public function suspend(Request $request, User $user, SuspendUser $suspendUser): RedirectResponse
    {
        abort_if($request->user()->id === $user->id, 403, 'You cannot suspend yourself.');

        $suspendUser->suspend($user);

        $this->postHog->capture($request->user(), 'user_suspended', [
            'target_user_id' => $user->id,
        ]);

        return back();
    }

    /**
     * Unsuspend a user.
     */
    public function unsuspend(Request $request, User $user, SuspendUser $suspendUser): RedirectResponse
    {
        $suspendUser->unsuspend($user);

        $this->postHog->capture($request->user(), 'user_unsuspended', [
            'target_user_id' => $user->id,
        ]);

        return back();
    }

    /**
     * Ban a user (delete + add email to banned list).
     */
    public function ban(BanUserRequest $request, User $user, BanUser $banUser): RedirectResponse
    {
        abort_if($request->user()->id === $user->id, 403, 'You cannot ban yourself.');

        $banUser->handle($user, $request->user(), $request->validated()['reason'] ?? null);

        $this->postHog->capture($request->user(), 'user_banned', [
            'target_user_id' => $user->id,
        ]);

        return to_route('admin.users.index');
    }

    /**
     * Delete a user (anonymize without banning email).
     */
    public function destroy(Request $request, User $user, DeleteUser $deleteUser): RedirectResponse
    {
        abort_if($request->user()->id === $user->id, 403, 'You cannot delete yourself.');

        $deleteUser->handle($user);

        $this->postHog->capture($request->user(), 'user_deleted', [
            'target_user_id' => $user->id,
        ]);

        return to_route('admin.users.index');
    }
}
