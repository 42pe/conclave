<?php

namespace App\Http\Controllers\Admin;

use App\Actions\BanUser;
use App\Actions\DeleteUser;
use App\Actions\SuspendUser;
use App\Actions\UnsuspendUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BanUserRequest;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Models\User;
use App\Services\PostHogService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserModerationController extends Controller
{
    public function __construct(
        private PostHogService $postHog,
    ) {}
    /**
     * Display a paginated listing of users.
     */
    public function index(): Response
    {
        return Inertia::render('admin/users/index', [
            'users' => User::query()
                ->orderByDesc('created_at')
                ->paginate(20),
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
        User::create([
            ...$request->validated(),
            'email_verified_at' => now(),
        ]);

        return to_route('admin.users.index');
    }

    /**
     * Suspend the given user.
     */
    public function suspend(User $user, SuspendUser $action): RedirectResponse
    {
        if ($user->isAdmin()) {
            abort(403, 'Cannot suspend an admin user.');
        }

        $action->handle($user);

        $this->postHog->capture((string) request()->user()->id, 'user_suspended', [
            'target_user_id' => $user->id,
        ]);

        return back();
    }

    /**
     * Unsuspend the given user.
     */
    public function unsuspend(User $user, UnsuspendUser $action): RedirectResponse
    {
        $action->handle($user);

        return back();
    }

    /**
     * Ban the given user.
     */
    public function ban(BanUserRequest $request, User $user, BanUser $action): RedirectResponse
    {
        if ($user->isAdmin()) {
            abort(403, 'Cannot ban an admin user.');
        }

        $action->handle($user, $request->user(), $request->validated('reason'));

        $this->postHog->capture((string) $request->user()->id, 'user_banned', [
            'target_user_id' => $user->id,
        ]);

        return to_route('admin.users.index');
    }

    /**
     * Delete (anonymize) the given user without banning their email.
     */
    public function delete(User $user, DeleteUser $action): RedirectResponse
    {
        if ($user->isAdmin()) {
            abort(403, 'Cannot delete an admin user.');
        }

        $action->handle($user);

        $this->postHog->capture((string) request()->user()->id, 'user_deleted', [
            'target_user_id' => $user->id,
        ]);

        return to_route('admin.users.index');
    }
}
