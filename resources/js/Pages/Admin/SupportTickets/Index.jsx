import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

const STATUS_VARIANT = { open: 'warn', pending: 'good', closed: 'locked' };

export default function Index({ tickets }) {
    const rows = tickets.data ?? [];

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold">Support Tickets</h2>}>
            <Head title="Admin — Support Tickets" />

            <Card>
                <CardHeader><CardTitle>All tickets ({tickets.total})</CardTitle></CardHeader>
                <CardContent>
                    {rows.length === 0 ? (
                        <p className="text-sm text-muted-foreground">No support tickets yet.</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="text-left text-xs uppercase text-muted-foreground">
                                <tr>
                                    <th className="py-2">Subject</th>
                                    <th className="py-2">Team</th>
                                    <th className="py-2">Submitted by</th>
                                    <th className="py-2">Status</th>
                                    <th className="py-2">Updated</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {rows.map((ticket) => (
                                    <tr key={ticket.id}>
                                        <td className="py-2">
                                            <Link href={route('admin.support-tickets.show', ticket.id)} className="font-medium hover:underline">
                                                {ticket.subject}
                                            </Link>
                                        </td>
                                        <td className="py-2">{ticket.team?.name}</td>
                                        <td className="py-2 text-muted-foreground">{ticket.user?.email}</td>
                                        <td className="py-2"><Badge variant={STATUS_VARIANT[ticket.status] ?? 'locked'}>{ticket.status}</Badge></td>
                                        <td className="py-2 text-muted-foreground">{new Date(ticket.updated_at).toLocaleString()}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                    <Pagination paginator={tickets} />
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
