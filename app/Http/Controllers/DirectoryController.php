<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DirectoryController extends Controller
{
    /**
     * Display the user directory.
     */
    public function index(Request $request): Response
    {
        $users = User::query()
            ->where('is_deleted', false)
            ->where('show_in_directory', true)
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('preferred_name', 'like', "%{$search}%");
                });
            })
            ->when($request->input('sort') === 'newest', function ($query) {
                $query->orderByDesc('created_at');
            }, function ($query) {
                $query->orderBy('name');
            })
            ->paginate(24, ['id', 'name', 'username', 'preferred_name', 'avatar_path', 'bio', 'role', 'is_deleted', 'created_at'])
            ->withQueryString();

        return Inertia::render('directory/index', [
            'users' => $users,
            'filters' => [
                'search' => $request->input('search', ''),
                'sort' => $request->input('sort', 'name'),
            ],
        ]);
    }
}
