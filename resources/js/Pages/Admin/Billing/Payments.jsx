import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

const STATUS_VARIANT = { succeeded: 'good', pending: 'warn', failed: 'critical', refunded: 'locked' };

function money(amount, currency) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(amount / 100);
}

export default function Payments({ payments }) {
    const rows = payments.data ?? [];

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold">Payments</h2>}>
            <Head title="Admin — Payments" />

            <Card>
                <CardHeader><CardTitle>All payments ({payments.total})</CardTitle></CardHeader>
                <CardContent>
                    {rows.length === 0 ? (
                        <p className="text-sm text-muted-foreground">No payments yet.</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="text-left text-xs uppercase text-muted-foreground">
                                <tr>
                                    <th className="py-2">Team</th>
                                    <th className="py-2">Provider</th>
                                    <th className="py-2">Amount</th>
                                    <th className="py-2">Status</th>
                                    <th className="py-2">Paid</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {rows.map((payment) => (
                                    <tr key={payment.id}>
                                        <td className="py-2">{payment.team?.name}</td>
                                        <td className="py-2 capitalize">{payment.provider}</td>
                                        <td className="py-2">{money(payment.amount, payment.currency)}</td>
                                        <td className="py-2"><Badge variant={STATUS_VARIANT[payment.status] ?? 'locked'}>{payment.status}</Badge></td>
                                        <td className="py-2 text-muted-foreground">{payment.paid_at ? new Date(payment.paid_at).toLocaleDateString() : '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                    <Pagination paginator={payments} />
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
