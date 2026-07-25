import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

export default function Index({ coupons = [] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
        type: 'percent',
        value: '',
        max_redemptions: '',
        valid_until: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('admin.coupons.store'), { preserveScroll: true, onSuccess: () => reset() });
    }

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold">Coupons</h2>}>
            <Head title="Admin — Coupons" />

            <Card className="mb-6">
                <CardHeader><CardTitle>Create coupon</CardTitle></CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-4 sm:grid-cols-5 sm:items-end">
                        <div>
                            <Label htmlFor="code">Code</Label>
                            <Input id="code" value={data.code} onChange={(e) => setData('code', e.target.value)} />
                            {errors.code && <p className="mt-1 text-xs text-destructive">{errors.code}</p>}
                        </div>
                        <div>
                            <Label>Type</Label>
                            <Select value={data.type} onValueChange={(v) => setData('type', v)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="percent">Percent</SelectItem>
                                    <SelectItem value="fixed">Fixed (minor units)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label htmlFor="value">Value</Label>
                            <Input id="value" type="number" min="1" value={data.value} onChange={(e) => setData('value', e.target.value)} />
                            {errors.value && <p className="mt-1 text-xs text-destructive">{errors.value}</p>}
                        </div>
                        <div>
                            <Label htmlFor="max_redemptions">Max redemptions</Label>
                            <Input
                                id="max_redemptions"
                                type="number"
                                min="1"
                                value={data.max_redemptions}
                                onChange={(e) => setData('max_redemptions', e.target.value)}
                                placeholder="Unlimited"
                            />
                        </div>
                        <div>
                            <Label htmlFor="valid_until">Expires</Label>
                            <Input
                                id="valid_until"
                                type="date"
                                value={data.valid_until}
                                onChange={(e) => setData('valid_until', e.target.value)}
                            />
                        </div>
                        <Button type="submit" disabled={processing} className="sm:col-span-5 sm:w-fit">Create coupon</Button>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>All coupons</CardTitle></CardHeader>
                <CardContent>
                    <table className="w-full text-sm">
                        <thead className="text-left text-xs uppercase text-muted-foreground">
                            <tr>
                                <th className="py-2">Code</th>
                                <th className="py-2">Value</th>
                                <th className="py-2">Redeemed</th>
                                <th className="py-2">Expires</th>
                                <th className="py-2">Status</th>
                                <th className="py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {coupons.map((coupon) => (
                                <tr key={coupon.id}>
                                    <td className="py-2 font-mono">{coupon.code}</td>
                                    <td className="py-2">{coupon.type === 'percent' ? `${coupon.value}%` : coupon.value}</td>
                                    <td className="py-2">{coupon.times_redeemed}{coupon.max_redemptions ? ` / ${coupon.max_redemptions}` : ''}</td>
                                    <td className="py-2 text-muted-foreground">
                                        {coupon.valid_until ? new Date(coupon.valid_until).toLocaleDateString() : 'Never'}
                                    </td>
                                    <td className="py-2">
                                        <Badge variant={coupon.is_active ? 'good' : 'locked'}>{coupon.is_active ? 'Active' : 'Inactive'}</Badge>
                                    </td>
                                    <td className="py-2 text-right space-x-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => router.patch(route('admin.coupons.update', coupon.id), { is_active: !coupon.is_active }, { preserveScroll: true })}
                                        >
                                            {coupon.is_active ? 'Deactivate' : 'Activate'}
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            onClick={() => {
                                                if (confirm('Delete this coupon?')) {
                                                    router.delete(route('admin.coupons.destroy', coupon.id), { preserveScroll: true });
                                                }
                                            }}
                                        >
                                            Delete
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
