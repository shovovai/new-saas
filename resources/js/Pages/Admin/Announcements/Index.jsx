import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

const SEVERITY_VARIANT = { info: 'good', warning: 'warn', critical: 'critical' };

export default function Index({ announcements = [] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        body: '',
        severity: 'info',
        starts_at: '',
        ends_at: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('admin.announcements.store'), { preserveScroll: true, onSuccess: () => reset() });
    }

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold">Announcements</h2>}>
            <Head title="Admin — Announcements" />

            <Card className="mb-6">
                <CardHeader><CardTitle>Publish an announcement</CardTitle></CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-3">
                            <div className="sm:col-span-2">
                                <Label htmlFor="title">Title</Label>
                                <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} />
                                {errors.title && <p className="mt-1 text-xs text-destructive">{errors.title}</p>}
                            </div>
                            <div>
                                <Label>Severity</Label>
                                <Select value={data.severity} onValueChange={(v) => setData('severity', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="info">Info</SelectItem>
                                        <SelectItem value="warning">Warning</SelectItem>
                                        <SelectItem value="critical">Critical</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <div>
                            <Label htmlFor="body">Message</Label>
                            <textarea
                                id="body"
                                value={data.body}
                                onChange={(e) => setData('body', e.target.value)}
                                rows={3}
                                className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            />
                            {errors.body && <p className="mt-1 text-xs text-destructive">{errors.body}</p>}
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <Label htmlFor="starts_at">Starts (optional)</Label>
                                <Input id="starts_at" type="datetime-local" value={data.starts_at} onChange={(e) => setData('starts_at', e.target.value)} />
                            </div>
                            <div>
                                <Label htmlFor="ends_at">Ends (optional)</Label>
                                <Input id="ends_at" type="datetime-local" value={data.ends_at} onChange={(e) => setData('ends_at', e.target.value)} />
                            </div>
                        </div>
                        <Button type="submit" disabled={processing}>Publish</Button>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>All announcements</CardTitle></CardHeader>
                <CardContent className="divide-y divide-border">
                    {announcements.map((a) => (
                        <div key={a.id} className="flex items-start justify-between gap-4 py-3">
                            <div>
                                <div className="flex items-center gap-2">
                                    <p className="font-medium">{a.title}</p>
                                    <Badge variant={SEVERITY_VARIANT[a.severity] ?? 'good'}>{a.severity}</Badge>
                                    {!a.is_active && <Badge variant="locked">Inactive</Badge>}
                                </div>
                                <p className="mt-1 text-sm text-muted-foreground">{a.body}</p>
                            </div>
                            <div className="flex shrink-0 gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => router.patch(route('admin.announcements.update', a.id), { is_active: !a.is_active }, { preserveScroll: true })}
                                >
                                    {a.is_active ? 'Deactivate' : 'Activate'}
                                </Button>
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    onClick={() => {
                                        if (confirm('Delete this announcement?')) {
                                            router.delete(route('admin.announcements.destroy', a.id), { preserveScroll: true });
                                        }
                                    }}
                                >
                                    Delete
                                </Button>
                            </div>
                        </div>
                    ))}
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
