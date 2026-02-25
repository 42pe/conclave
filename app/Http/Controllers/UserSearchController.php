<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    /**
     * Search for users by name, username, or preferred name.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $users = User::query()
            ->where('is_deleted', false)
            ->where('id', '!=', $request->user()->id)
            ->where(function ($q) use ($query) {
                $q->where('username', 'LIKE', "%{$query}%")
                    ->orWhere('name', 'LIKE', "%{$query}%")
                    ->orWhere('preferred_name', 'LIKE', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'username', 'preferred_name', 'avatar_path']);

        $users->each->append('display_name');

        return response()->json($users);
    }
}
