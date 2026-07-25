import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

const STATUS_VARIANT = { open: 'warn', pending: 'good', closed: 'locked' };

export default function Show({ ticket }) {
    const { data, setData, post, processing, reset } = useForm({ message: '' });
    const statusForm = useForm({ status: ticket.status });

    function submitReply(e) {
        e.preventDefault();
        post(route('admin.support-tickets.reply', ticket.id), { preserveScroll: true, onSuccess: () => reset() });
    }

    function changeStatus(status) {
        statusForm.setData('status', status);
        statusForm.patch(route('admin.support-tickets.update-status', ticket.id), { preserveScroll: true });
    }

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold">{ticket.subject}</h2>}>
            <Head title={`Admin — Ticket #${ticket.id}`} />

            <div className="mb-4 flex items-center gap-3">
                <span className="text-sm text-muted-foreground">{ticket.team?.name} · {ticket.user?.email}</span>
                <Badge variant={STATUS_VARIANT[ticket.status] ?? 'locked'}>{ticket.status}</Badge>
                <Select value={data.status} onValueChange={changeStatus}>
                    <SelectTrigger className="w-40"><SelectValue placeholder="Change status" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="open">Open</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="closed">Closed</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <Card className="mb-6">
                <CardHeader><CardTitle>Conversation</CardTitle></CardHeader>
                <CardContent className="space-y-4">
                    {ticket.replies.map((reply) => (
                        <div key={reply.id} className={`rounded-lg border p-3 text-sm ${reply.is_admin_reply ? 'border-primary/30 bg-primary/5' : 'border-border'}`}>
                            <p className="mb-1 text-xs font-medium text-muted-foreground">
                                {reply.is_admin_reply ? 'Support team' : reply.user?.name} · {new Date(reply.created_at).toLocaleString()}
                            </p>
                            <p className="whitespace-pre-wrap">{reply.message}</p>
                        </div>
                    ))}
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>Reply</CardTitle></CardHeader>
                <CardContent>
                    <form onSubmit={submitReply} className="space-y-3">
                        <textarea
                            value={data.message}
                            onChange={(e) => setData('message', e.target.value)}
                            rows={4}
                            className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            placeholder="Write a reply…"
                        />
                        <Button type="submit" disabled={processing}>Send reply</Button>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
