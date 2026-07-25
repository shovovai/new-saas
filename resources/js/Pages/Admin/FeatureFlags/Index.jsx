import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Switch } from '@/Components/ui/switch';

export default function Index({ flags = [] }) {
    return (
        <AdminLayout header={<h2 className="text-xl font-semibold">Feature Flags</h2>}>
            <Head title="Admin — Feature Flags" />

            <Card className="mb-4 border-score-warn/30 bg-score-warn/5">
                <CardContent className="p-4 text-sm text-muted-foreground">
                    These are platform-wide kill switches — turning one off disables it for every team on every plan,
                    e.g. during an outage. This is separate from the per-plan feature matrix.
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>Global switches</CardTitle></CardHeader>
                <CardContent className="divide-y divide-border">
                    {flags.map((flag) => (
                        <div key={flag.id} className="flex items-center justify-between py-3">
                            <div>
                                <p className="font-medium">{flag.label}</p>
                                <p className="text-xs text-muted-foreground">{flag.key}</p>
                            </div>
                            <Switch
                                checked={flag.enabled}
                                onCheckedChange={(checked) =>
                                    router.patch(route('admin.feature-flags.update', flag.id), { enabled: checked }, { preserveScroll: true })
                                }
                            />
                        </div>
                    ))}
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
