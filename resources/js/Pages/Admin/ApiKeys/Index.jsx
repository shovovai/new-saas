import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

export default function Index({ keys }) {
    const rows = keys.data ?? [];

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold">API Keys</h2>}>
            <Head title="Admin — API Keys" />

            <Card>
                <CardHeader><CardTitle>All API keys ({keys.total})</CardTitle></CardHeader>
                <CardContent>
                    {rows.length === 0 ? (
                        <p className="text-sm text-muted-foreground">No API keys have been issued yet.</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="text-left text-xs uppercase text-muted-foreground">
                                <tr>
                                    <th className="py-2">Team</th>
                                    <th className="py-2">Name</th>
                                    <th className="py-2">Prefix</th>
                                    <th className="py-2">Last used</th>
                                    <th className="py-2">Status</th>
                                    <th className="py-2 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {rows.map((key) => (
                                    <tr key={key.id}>
                                        <td className="py-2">{key.team?.name}</td>
                                        <td className="py-2">{key.name}</td>
                                        <td className="py-2 font-mono text-xs">{key.key_prefix}…</td>
                                        <td className="py-2 text-muted-foreground">{key.last_used_at ? new Date(key.last_used_at).toLocaleString() : 'Never'}</td>
                                        <td className="py-2">
                                            <Badge variant={key.revoked_at ? 'critical' : 'good'}>{key.revoked_at ? 'Revoked' : 'Active'}</Badge>
                                        </td>
                                        <td className="py-2 text-right">
                                            {!key.revoked_at && (
                                                <Button
                                                    size="sm"
                                                    variant="destructive"
                                                    onClick={() => router.patch(route('admin.api-keys.revoke', key.id), {}, { preserveScroll: true })}
                                                >
                                                    Revoke
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                    <Pagination paginator={keys} />
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
