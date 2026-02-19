<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DirectoryController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->query('search', '');

        $users = User::query()
            ->where('is_deleted', false)
            ->where('show_in_directory', true)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('preferred_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('username')
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('directory/index', [
            'users' => $users,
            'search' => $search,
        ]);
    }
}
