import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

export default function Index({ verifications }) {
    const rows = verifications.data ?? [];

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold">Verification Requests</h2>}>
            <Head title="Admin — Verification Requests" />

            <Card>
                <CardHeader><CardTitle>Pending &amp; failed verifications across all teams</CardTitle></CardHeader>
                <CardContent>
                    {rows.length === 0 ? (
                        <p className="text-sm text-muted-foreground">Nothing pending — all clear.</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="text-left text-xs uppercase text-muted-foreground">
                                <tr>
                                    <th className="py-2">Website</th>
                                    <th className="py-2">Team</th>
                                    <th className="py-2">Method</th>
                                    <th className="py-2">Attempts</th>
                                    <th className="py-2">Last error</th>
                                    <th className="py-2">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {rows.map((v) => (
                                    <tr key={v.id}>
                                        <td className="py-2">{v.website?.domain}</td>
                                        <td className="py-2">{v.website?.team?.name}</td>
                                        <td className="py-2">{v.method}</td>
                                        <td className="py-2">{v.attempts}</td>
                                        <td className="max-w-xs truncate py-2 text-muted-foreground">{v.last_error ?? '—'}</td>
                                        <td className="py-2">
                                            <Badge variant={v.status === 'failed' ? 'critical' : 'warn'}>{v.status}</Badge>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
