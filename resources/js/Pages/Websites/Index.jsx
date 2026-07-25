import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card } from '@/Components/ui/card';

const STATUS_VARIANT = {
    verified: 'good',
    pending_verification: 'warn',
    paused: 'locked',
    failed: 'critical',
};

export default function Index({ websites = [], remainingSlots = 0 }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold">Websites</h2>
                    <Button asChild disabled={remainingSlots <= 0}>
                        <Link href={route('websites.create')}>
                            <Plus className="h-4 w-4" /> Add website
                        </Link>
                    </Button>
                </div>
            }
        >
            <Head title="Websites" />

            {remainingSlots <= 0 && (
                <div className="mb-4 rounded-lg border border-score-warn/30 bg-score-warn/10 p-4 text-sm">
                    You've reached your plan's website limit.{' '}
                    <Link href={route('billing.show')} className="font-medium text-primary hover:underline">
                        Upgrade your plan
                    </Link>{' '}
                    to add more sites.
                </div>
            )}

            {websites.length === 0 ? (
                <Card className="flex flex-col items-center gap-3 p-12 text-center">
                    <p className="text-muted-foreground">No websites added yet.</p>
                    <Button asChild>
                        <Link href={route('websites.create')}>
                            <Plus className="h-4 w-4" /> Add your first website
                        </Link>
                    </Button>
                </Card>
            ) : (
                <Card className="overflow-hidden">
                    <table className="w-full text-sm">
                        <thead className="border-b border-border bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                            <tr>
                                <th className="px-4 py-3 font-medium">Website</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3 font-medium">Group</th>
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {websites.map((site) => (
                                <tr key={site.id} className="hover:bg-accent/50">
                                    <td className="px-4 py-3">
                                        <Link href={route('websites.show', site.id)} className="font-medium hover:text-primary hover:underline">
                                            {site.name}
                                        </Link>
                                        <div className="text-xs text-muted-foreground">{site.domain}</div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge variant={STATUS_VARIANT[site.status] ?? 'outline'}>
                                            {site.status === 'pending_verification' ? 'Pending Verification' : site.status}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">{site.group ?? '—'}</td>
                                    <td className="px-4 py-3 text-right">
                                        {site.status === 'pending_verification' ? (
                                            <Link href={route('websites.show', site.id)} className="text-sm font-medium text-primary hover:underline">
                                                Resume verification
                                            </Link>
                                        ) : (
                                            <div className="flex justify-end gap-2">
                                                {site.status === 'paused' ? (
                                                    <Button size="sm" variant="outline" onClick={() => router.post(route('websites.resume', site.id))}>
                                                        Resume
                                                    </Button>
                                                ) : (
                                                    <Button size="sm" variant="outline" onClick={() => router.post(route('websites.pause', site.id))}>
                                                        Pause
                                                    </Button>
                                                )}
                                                <Button
                                                    size="sm"
                                                    variant="destructive"
                                                    onClick={() => {
                                                        if (confirm('Remove this website?')) {
                                                            router.delete(route('websites.destroy', site.id));
                                                        }
                                                    }}
                                                >
                                                    Remove
                                                </Button>
                                            </div>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </Card>
            )}
        </AuthenticatedLayout>
    );
}
