<?php

namespace App\Http\Controllers\Admin;

use App\Actions\BanUser;
use App\Actions\DeleteUser;
use App\Actions\SuspendUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BanUserRequest;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserModerationController extends Controller
{
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
        User::create($request->validated());

        return to_route('admin.users.index');
    }

    /**
     * Suspend a user.
     */
    public function suspend(User $user, SuspendUser $suspendUser): RedirectResponse
    {
        $suspendUser->suspend($user);

        return back();
    }

    /**
     * Unsuspend a user.
     */
    public function unsuspend(User $user, SuspendUser $suspendUser): RedirectResponse
    {
        $suspendUser->unsuspend($user);

        return back();
    }

    /**
     * Ban a user (delete + add email to banned list).
     */
    public function ban(BanUserRequest $request, User $user, BanUser $banUser): RedirectResponse
    {
        $banUser->handle($user, $request->user(), $request->validated()['reason'] ?? null);

        return to_route('admin.users.index');
    }

    /**
     * Delete a user (anonymize without banning email).
     */
    public function destroy(User $user, DeleteUser $deleteUser): RedirectResponse
    {
        $deleteUser->handle($user);

        return to_route('admin.users.index');
    }
}
