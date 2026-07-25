<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $config = config('permissions');

        $permissionIds = [];

        foreach ($config['permissions'] as $key => $meta) {
            $permission = Permission::updateOrCreate(
                ['key' => $key],
                ['label' => $meta['label'], 'group' => $meta['group'] ?? null],
            );

            $permissionIds[$key] = $permission->id;
        }

        foreach ($config['defaults'] as $role => $keys) {
            // '*' means "every known permission" — expanded here rather than
            // stored as a literal wildcard row, since role_permissions.permission_id
            // is a real foreign key.
            $resolvedKeys = $keys === ['*'] ? array_keys($permissionIds) : $keys;

            foreach ($resolvedKeys as $key) {
                if (! isset($permissionIds[$key])) {
                    continue;
                }

                RolePermission::firstOrCreate([
                    'role' => $role,
                    'permission_id' => $permissionIds[$key],
                ]);
            }
        }
    }
}
