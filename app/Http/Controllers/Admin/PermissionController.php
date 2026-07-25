<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Live editor for the role -> permission matrix (Functional Spec §13).
 * config/permissions.php remains the seed data; role_permissions is the
 * runtime source of truth once seeded (see PermissionService).
 */
class PermissionController extends Controller
{
    public function index(): Response
    {
        $permissions = Permission::query()->orderBy('group')->orderBy('key')->get();
        $matrix = RolePermission::query()->get(['role', 'permission_id'])
            ->groupBy('role')
            ->map(fn ($rows) => $rows->pluck('permission_id')->all());

        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => $permissions,
            'roles' => collect(TeamRole::cases())->map(fn (TeamRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
            'matrix' => $matrix,
        ]);
    }

    public function toggle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:'.implode(',', TeamRole::values())],
            'permission_id' => ['required', 'exists:permissions,id'],
            'granted' => ['required', 'boolean'],
        ]);

        if ($validated['granted']) {
            RolePermission::query()->firstOrCreate([
                'role' => $validated['role'],
                'permission_id' => $validated['permission_id'],
            ]);
        } else {
            RolePermission::query()
                ->where('role', $validated['role'])
                ->where('permission_id', $validated['permission_id'])
                ->delete();
        }

        return back()->with('success', 'Permission matrix updated.');
    }
}
