<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->withCount('teams')
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only('search'),
        ]);
    }

    public function toggleAdmin(User $user): RedirectResponse
    {
        $user->update(['is_platform_admin' => ! $user->is_platform_admin]);

        return back()->with('success', $user->is_platform_admin ? "{$user->name} is now a platform admin." : "{$user->name} is no longer a platform admin.");
    }

    public function toggleSuspension(User $user): RedirectResponse
    {
        $user->update(['suspended_at' => $user->suspended_at ? null : now()]);

        return back()->with('success', $user->suspended_at ? "{$user->name} has been suspended." : "{$user->name} has been reinstated.");
    }
}
