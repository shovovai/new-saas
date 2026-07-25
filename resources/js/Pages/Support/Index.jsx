import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { LifeBuoy } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

const STATUS_VARIANT = { open: 'warn', pending: 'good', closed: 'locked' };

export default function Index({ tickets = [] }) {
    const { data, setData, post, processing, errors, reset } = useForm({ subject: '', message: '' });

    function submit(e) {
        e.preventDefault();
        post(route('support.store'), { preserveScroll: true, onSuccess: () => reset() });
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Support</h2>}>
            <Head title="Support" />

            <Card className="mx-auto max-w-2xl">
                <CardContent className="flex items-center gap-3 p-6 text-sm text-muted-foreground">
                    <LifeBuoy className="h-6 w-6 shrink-0" />
                    Open a ticket below, or email support@siteguardian.ai. Priority support response times depend on your plan.
                </CardContent>
            </Card>

            <Card className="mx-auto mt-6 max-w-2xl">
                <CardHeader><CardTitle>New ticket</CardTitle></CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label htmlFor="subject">Subject</Label>
                            <Input id="subject" value={data.subject} onChange={(e) => setData('subject', e.target.value)} />
                            {errors.subject && <p className="mt-1 text-xs text-destructive">{errors.subject}</p>}
                        </div>
                        <div>
                            <Label htmlFor="message">Message</Label>
                            <textarea
                                id="message"
                                value={data.message}
                                onChange={(e) => setData('message', e.target.value)}
                                rows={4}
                                className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            />
                            {errors.message && <p className="mt-1 text-xs text-destructive">{errors.message}</p>}
                        </div>
                        <Button type="submit" disabled={processing}>Submit ticket</Button>
                    </form>
                </CardContent>
            </Card>

            <Card className="mx-auto mt-6 max-w-2xl">
                <CardHeader><CardTitle>Your tickets</CardTitle></CardHeader>
                <CardContent className="divide-y divide-border">
                    {tickets.length === 0 ? (
                        <p className="text-sm text-muted-foreground">You haven't submitted any tickets yet.</p>
                    ) : (
                        tickets.map((ticket) => (
                            <div key={ticket.id} className="py-3">
                                <div className="flex items-center justify-between gap-3">
                                    <p className="font-medium">{ticket.subject}</p>
                                    <Badge variant={STATUS_VARIANT[ticket.status] ?? 'locked'}>{ticket.status}</Badge>
                                </div>
                                <div className="mt-2 space-y-2">
                                    {ticket.replies.map((reply) => (
                                        <div key={reply.id} className={`rounded-lg border p-2 text-sm ${reply.is_admin_reply ? 'border-primary/30 bg-primary/5' : 'border-border'}`}>
                                            <p className="mb-1 text-xs text-muted-foreground">
                                                {reply.is_admin_reply ? 'Support team' : reply.user?.name}
                                            </p>
                                            <p className="whitespace-pre-wrap">{reply.message}</p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ))
                    )}
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
