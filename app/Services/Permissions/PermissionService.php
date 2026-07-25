<?php

namespace App\Services\Permissions;

use App\Models\RolePermission;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Data-driven role permissions: which permission keys a role grants is
 * read from the `role_permissions` table (seeded from config/permissions.php),
 * never hardcoded to a role-name check in application code.
 */
class PermissionService
{
    public function can(User $user, Team $team, string $permissionKey): bool
    {
        if ($user->is_platform_admin) {
            return true;
        }

        $role = $team->roleFor($user);

        if ($role === null) {
            return false;
        }

        return in_array($permissionKey, $this->permissionsForRole($role), true);
    }

    /**
     * @return list<string>
     */
    public function permissionsForRole(string $role): array
    {
        return Cache::remember("role_permissions:{$role}", 300, function () use ($role) {
            return RolePermission::query()
                ->where('role', $role)
                ->with('permission')
                ->get()
                ->pluck('permission.key')
                ->filter()
                ->values()
                ->all();
        });
    }

    public function forgetCache(string $role): void
    {
        Cache::forget("role_permissions:{$role}");
    }
}
