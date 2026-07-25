import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router, useForm } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';

export default function Index({ users, filters }) {
    const { data, setData, get } = useForm({ search: filters?.search ?? '' });
    const rows = users.data ?? [];

    function search(e) {
        e.preventDefault();
        get(route('admin.users.index'), { preserveState: true });
    }

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold">Users</h2>}>
            <Head title="Admin — Users" />

            <form onSubmit={search} className="mb-4 flex gap-2">
                <Input
                    value={data.search}
                    onChange={(e) => setData('search', e.target.value)}
                    placeholder="Search name or email…"
                    className="max-w-sm"
                />
                <Button type="submit" variant="secondary">Search</Button>
            </form>

            <Card>
                <CardHeader><CardTitle>All users ({users.total})</CardTitle></CardHeader>
                <CardContent>
                    <table className="w-full text-sm">
                        <thead className="text-left text-xs uppercase text-muted-foreground">
                            <tr>
                                <th className="py-2">Name</th>
                                <th className="py-2">Email</th>
                                <th className="py-2">Teams</th>
                                <th className="py-2">Status</th>
                                <th className="py-2">Joined</th>
                                <th className="py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {rows.map((user) => (
                                <tr key={user.id}>
                                    <td className="py-2">{user.name}</td>
                                    <td className="py-2 text-muted-foreground">{user.email}</td>
                                    <td className="py-2">{user.teams_count}</td>
                                    <td className="py-2 space-x-1">
                                        {user.is_platform_admin && <Badge variant="good">Admin</Badge>}
                                        {user.suspended_at && <Badge variant="critical">Suspended</Badge>}
                                    </td>
                                    <td className="py-2 text-muted-foreground">{new Date(user.created_at).toLocaleDateString()}</td>
                                    <td className="py-2 text-right space-x-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => router.patch(route('admin.users.toggle-admin', user.id), {}, { preserveScroll: true })}
                                        >
                                            {user.is_platform_admin ? 'Revoke admin' : 'Make admin'}
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant={user.suspended_at ? 'outline' : 'destructive'}
                                            onClick={() => router.patch(route('admin.users.toggle-suspension', user.id), {}, { preserveScroll: true })}
                                        >
                                            {user.suspended_at ? 'Reinstate' : 'Suspend'}
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    <Pagination paginator={users} />
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
