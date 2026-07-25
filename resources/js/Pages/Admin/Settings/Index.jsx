import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';

export default function Index({ settings = {} }) {
    const { data, setData, put, processing } = useForm({
        site_name: settings.site_name ?? 'SiteGuardian AI',
        support_email: settings.support_email ?? '',
        maintenance_mode: settings.maintenance_mode === '1',
        maintenance_message: settings.maintenance_message ?? '',
    });

    function submit(e) {
        e.preventDefault();
        put(route('admin.settings.update'), { preserveScroll: true });
    }

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold">System Settings</h2>}>
            <Head title="Admin — Settings" />

            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>Platform settings</CardTitle>
                    <CardDescription>Maintenance mode is a real switch — enabling it turns away every non-admin request site-wide.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label htmlFor="site_name">Site name</Label>
                            <Input id="site_name" value={data.site_name} onChange={(e) => setData('site_name', e.target.value)} />
                        </div>
                        <div>
                            <Label htmlFor="support_email">Support email</Label>
                            <Input id="support_email" type="email" value={data.support_email} onChange={(e) => setData('support_email', e.target.value)} />
                        </div>
                        <div className="flex items-center justify-between rounded-lg border border-border p-3">
                            <div>
                                <p className="text-sm font-medium">Maintenance mode</p>
                                <p className="text-xs text-muted-foreground">Blocks all non-admin traffic with a 503.</p>
                            </div>
                            <Switch checked={data.maintenance_mode} onCheckedChange={(checked) => setData('maintenance_mode', checked)} />
                        </div>
                        <div>
                            <Label htmlFor="maintenance_message">Maintenance message</Label>
                            <textarea
                                id="maintenance_message"
                                value={data.maintenance_message}
                                onChange={(e) => setData('maintenance_message', e.target.value)}
                                rows={2}
                                className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            />
                        </div>
                        <Button type="submit" disabled={processing}>Save settings</Button>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
