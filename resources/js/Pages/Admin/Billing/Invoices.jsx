import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

const STATUS_VARIANT = { paid: 'good', open: 'warn', draft: 'locked', void: 'locked', uncollectible: 'critical' };

function money(amount, currency) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(amount / 100);
}

export default function Invoices({ invoices }) {
    const rows = invoices.data ?? [];

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold">Invoices</h2>}>
            <Head title="Admin — Invoices" />

            <Card>
                <CardHeader><CardTitle>All invoices ({invoices.total})</CardTitle></CardHeader>
                <CardContent>
                    {rows.length === 0 ? (
                        <p className="text-sm text-muted-foreground">No invoices yet.</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="text-left text-xs uppercase text-muted-foreground">
                                <tr>
                                    <th className="py-2">Number</th>
                                    <th className="py-2">Team</th>
                                    <th className="py-2">Amount</th>
                                    <th className="py-2">Status</th>
                                    <th className="py-2">Issued</th>
                                    <th className="py-2">Paid</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {rows.map((invoice) => (
                                    <tr key={invoice.id}>
                                        <td className="py-2 font-mono">{invoice.number}</td>
                                        <td className="py-2">{invoice.team?.name}</td>
                                        <td className="py-2">{money(invoice.amount, invoice.currency)}</td>
                                        <td className="py-2"><Badge variant={STATUS_VARIANT[invoice.status] ?? 'locked'}>{invoice.status}</Badge></td>
                                        <td className="py-2 text-muted-foreground">{invoice.issued_at ? new Date(invoice.issued_at).toLocaleDateString() : '—'}</td>
                                        <td className="py-2 text-muted-foreground">{invoice.paid_at ? new Date(invoice.paid_at).toLocaleDateString() : '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                    <Pagination paginator={invoices} />
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
