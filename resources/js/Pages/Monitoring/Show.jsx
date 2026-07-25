import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { Activity } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

const STATUS_VARIANT = { ok: 'good', warning: 'warn', critical: 'critical' };

function StatusCard({ title, log, unit }) {
    return (
        <Card>
            <CardHeader className="pb-2"><CardTitle>{title}</CardTitle></CardHeader>
            <CardContent>
                {log ? (
                    <div className="flex items-center justify-between">
                        <span className="text-2xl font-semibold">{log.metric_value ?? '—'}{unit}</span>
                        <Badge variant={STATUS_VARIANT[log.status] ?? 'outline'}>{log.status}</Badge>
                    </div>
                ) : (
                    <p className="text-sm text-muted-foreground">No checks yet.</p>
                )}
            </CardContent>
        </Card>
    );
}

export default function Show({ website, available, logs = [], latestUptime, latestSsl, latestDomain }) {
    if (!available) {
        return (
            <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Monitoring</h2>}>
                <Head title="Monitoring" />
                <Card className="mx-auto max-w-lg">
                    <CardContent className="flex flex-col items-center gap-3 p-10 text-center">
                        <Activity className="h-8 w-8 text-muted-foreground" />
                        <p className="text-sm text-muted-foreground">
                            {website.status !== 'verified'
                                ? 'Verify this website to unlock continuous monitoring.'
                                : 'Monitoring is not included in your current plan.'}
                        </p>
                    </CardContent>
                </Card>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Monitoring — {website.domain}</h2>}>
            <Head title={`Monitoring — ${website.domain}`} />

            <div className="grid gap-4 sm:grid-cols-3">
                <StatusCard title="Response time" log={latestUptime} unit="ms" />
                <StatusCard title="SSL expiry" log={latestSsl} unit="d" />
                <StatusCard title="Domain expiry" log={latestDomain} unit="d" />
            </div>

            <Card className="mt-6">
                <CardHeader><CardTitle>Recent checks</CardTitle></CardHeader>
                <CardContent>
                    {logs.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No monitoring checks have run yet — they're dispatched automatically based on your plan's check frequency.
                        </p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="text-left text-xs uppercase text-muted-foreground">
                                <tr>
                                    <th className="py-2">Check</th>
                                    <th className="py-2">Status</th>
                                    <th className="py-2">Value</th>
                                    <th className="py-2">When</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {logs.map((log) => (
                                    <tr key={log.id}>
                                        <td className="py-2 capitalize">{log.check_type.replace('_', ' ')}</td>
                                        <td className="py-2"><Badge variant={STATUS_VARIANT[log.status] ?? 'outline'}>{log.status}</Badge></td>
                                        <td className="py-2">{log.metric_value ?? '—'}</td>
                                        <td className="py-2 text-muted-foreground">{new Date(log.created_at).toLocaleString()}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
