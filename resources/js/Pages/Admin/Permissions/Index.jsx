import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import { Fragment } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

export default function Index({ permissions = [], roles = [], matrix = {} }) {
    const grouped = permissions.reduce((acc, p) => {
        (acc[p.group ?? 'Other'] ??= []).push(p);
        return acc;
    }, {});

    function isGranted(role, permissionId) {
        return (matrix[role] ?? []).includes(permissionId);
    }

    function toggle(role, permission) {
        router.post(
            route('admin.permissions.toggle'),
            { role, permission_id: permission.id, granted: !isGranted(role, permission.id) },
            { preserveScroll: true },
        );
    }

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold">Roles &amp; Permissions</h2>}>
            <Head title="Admin — Roles & Permissions" />

            <Card>
                <CardHeader>
                    <CardTitle>Role → permission matrix</CardTitle>
                    <CardDescription>
                        Edits here write directly to role_permissions — the same table PermissionService reads at request time.
                    </CardDescription>
                </CardHeader>
                <CardContent className="overflow-x-auto">
                    <table className="w-full min-w-[700px] text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase text-muted-foreground">
                                <th className="py-2 pr-4">Permission</th>
                                {roles.map((role) => (
                                    <th key={role.value} className="px-3 py-2 text-center">{role.label}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {Object.entries(grouped).map(([group, perms]) => (
                                <Fragment key={group}>
                                    <tr>
                                        <td colSpan={roles.length + 1} className="pt-4 pb-1 text-xs font-semibold uppercase text-muted-foreground">
                                            {group}
                                        </td>
                                    </tr>
                                    {perms.map((permission) => (
                                        <tr key={permission.id} className="border-t border-border">
                                            <td className="py-2 pr-4">{permission.label}</td>
                                            {roles.map((role) => (
                                                <td key={role.value} className="px-3 py-2 text-center">
                                                    <input
                                                        type="checkbox"
                                                        checked={isGranted(role.value, permission.id)}
                                                        onChange={() => toggle(role.value, permission)}
                                                        className="h-4 w-4 rounded border-input"
                                                    />
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </Fragment>
                            ))}
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
