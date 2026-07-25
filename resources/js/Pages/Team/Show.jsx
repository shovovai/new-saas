import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';

export default function Show({ members = [], pendingInvitations = [], roles = [], canManage, canInvite }) {
    const { data, setData, post, processing, reset, errors } = useForm({ email: '', role: 'viewer' });

    function invite(e) {
        e.preventDefault();
        post(route('team.invite'), { onSuccess: () => reset() });
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Team</h2>}>
            <Head title="Team" />

            {canInvite && (
                <Card className="mb-6">
                    <CardHeader><CardTitle>Invite a member</CardTitle></CardHeader>
                    <CardContent>
                        <form onSubmit={invite} className="flex flex-wrap gap-2">
                            <Input
                                placeholder="Email address"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                className="max-w-xs"
                            />
                            <Select value={data.role} onValueChange={(v) => setData('role', v)}>
                                <SelectTrigger className="w-48"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    {roles.map((r) => <SelectItem key={r.value} value={r.value}>{r.label}</SelectItem>)}
                                </SelectContent>
                            </Select>
                            <Button type="submit" disabled={processing}>Send invitation</Button>
                        </form>
                        {errors.email && <p className="mt-2 text-sm text-destructive">{errors.email}</p>}
                    </CardContent>
                </Card>
            )}

            {pendingInvitations.length > 0 && (
                <Card className="mb-6">
                    <CardHeader><CardTitle>Pending invitations</CardTitle></CardHeader>
                    <CardContent>
                        <ul className="divide-y divide-border">
                            {pendingInvitations.map((inv) => (
                                <li key={inv.id} className="flex items-center justify-between py-2 text-sm">
                                    <div>
                                        <span className="font-medium">{inv.email}</span>
                                        <Badge variant="outline" className="ml-2">{inv.role}</Badge>
                                    </div>
                                    {canInvite && (
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            onClick={() => router.delete(route('team.revoke-invitation', inv.id))}
                                        >
                                            Revoke
                                        </Button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </CardContent>
                </Card>
            )}

            <Card>
                <CardHeader><CardTitle>Members</CardTitle></CardHeader>
                <CardContent>
                    <ul className="divide-y divide-border">
                        {members.map((m) => (
                            <li key={m.id} className="flex items-center justify-between py-3 text-sm">
                                <div>
                                    <p className="font-medium">{m.name}</p>
                                    <p className="text-xs text-muted-foreground">{m.email}</p>
                                </div>
                                {canManage ? (
                                    <div className="flex items-center gap-2">
                                        <Select
                                            value={m.pivot?.role}
                                            onValueChange={(v) => router.patch(route('team.update-role', m.id), { role: v }, { preserveScroll: true })}
                                        >
                                            <SelectTrigger className="w-40"><SelectValue /></SelectTrigger>
                                            <SelectContent>
                                                {roles.map((r) => <SelectItem key={r.value} value={r.value}>{r.label}</SelectItem>)}
                                            </SelectContent>
                                        </Select>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            onClick={() => router.delete(route('team.remove-member', m.id))}
                                        >
                                            Remove
                                        </Button>
                                    </div>
                                ) : (
                                    <span className="text-muted-foreground">{m.pivot?.role}</span>
                                )}
                            </li>
                        ))}
                    </ul>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
