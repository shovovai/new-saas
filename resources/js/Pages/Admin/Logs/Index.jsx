import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';

export default function Index({ logs, filters }) {
    const { data, setData, get } = useForm({ action: filters?.action ?? '' });
    const rows = logs.data ?? [];

    function search(e) {
        e.preventDefault();
        get(route('admin.logs.index'), { preserveState: true });
    }

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold">Audit Logs</h2>}>
            <Head title="Admin — Logs" />

            <form onSubmit={search} className="mb-4 flex gap-2">
                <Input
                    value={data.action}
                    onChange={(e) => setData('action', e.target.value)}
                    placeholder="Filter by action, e.g. pentest.run…"
                    className="max-w-sm"
                />
                <Button type="submit" variant="secondary">Filter</Button>
            </form>

            <Card>
                <CardHeader><CardTitle>{logs.total} entries</CardTitle></CardHeader>
                <CardContent>
                    <table className="w-full text-sm">
                        <thead className="text-left text-xs uppercase text-muted-foreground">
                            <tr>
                                <th className="py-2">Action</th>
                                <th className="py-2">Team</th>
                                <th className="py-2">User</th>
                                <th className="py-2">Subject</th>
                                <th className="py-2">IP</th>
                                <th className="py-2">When</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {rows.map((log) => (
                                <tr key={log.id}>
                                    <td className="py-2 font-mono text-xs">{log.action}</td>
                                    <td className="py-2">{log.team?.name ?? '—'}</td>
                                    <td className="py-2">{log.user?.name ?? '—'}</td>
                                    <td className="py-2 text-muted-foreground">{log.subject_type ? `${log.subject_type.split('\\').pop()} #${log.subject_id}` : '—'}</td>
                                    <td className="py-2 text-muted-foreground">{log.ip_address ?? '—'}</td>
                                    <td className="py-2 text-muted-foreground">{new Date(log.created_at).toLocaleString()}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    <Pagination paginator={logs} />
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
