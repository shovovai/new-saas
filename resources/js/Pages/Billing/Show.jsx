import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogDescription,
} from '@/Components/ui/dialog';

function money(cents) {
    return cents === 0 ? 'Free' : `$${(cents / 100).toFixed(0)}/mo`;
}

const PROVIDERS = [
    { value: 'stripe', label: 'Stripe (cards, global)' },
    { value: 'paddle', label: 'Paddle (cards, global, merchant of record)' },
    { value: 'sslcommerz', label: 'SSLCommerz (Bangladesh)' },
];

export default function Show({ subscription, plans = [], invoices = [] }) {
    const [pendingPlan, setPendingPlan] = useState(null);
    const [provider, setProvider] = useState('stripe');

    function switchPlan(plan) {
        if (plan.price_monthly === 0) {
            router.post(route('billing.checkout'), { plan_id: plan.id, billing_cycle: 'monthly' });
            return;
        }
        setPendingPlan(plan);
    }

    function confirmCheckout() {
        router.post(route('billing.checkout'), {
            plan_id: pendingPlan.id,
            billing_cycle: 'monthly',
            provider,
        });
        setPendingPlan(null);
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Billing</h2>}>
            <Head title="Billing" />

            {subscription && (
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="text-foreground">Current plan</CardTitle>
                        <CardDescription>
                            {subscription.plan.name} — {money(subscription.plan.price_monthly)} · status: {subscription.status}
                        </CardDescription>
                    </CardHeader>
                </Card>
            )}

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {plans.map((plan) => {
                    const isCurrent = subscription?.plan?.id === plan.id;
                    return (
                        <Card key={plan.id} className={isCurrent ? 'border-primary' : ''}>
                            <CardHeader>
                                <CardTitle className="flex items-center justify-between text-foreground">
                                    {plan.name}
                                    {isCurrent && <Badge>Current</Badge>}
                                </CardTitle>
                                <CardDescription>{plan.description}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <p className="text-2xl font-semibold">{money(plan.price_monthly)}</p>
                                <ul className="space-y-1 text-sm text-muted-foreground">
                                    <li>Up to {plan.max_websites} websites</li>
                                    <li>Up to {plan.max_team_members} team members</li>
                                    <li>{plan.max_scans_per_month} scans/month</li>
                                    <li>{plan.ai_credits} AI credits</li>
                                </ul>
                                {!isCurrent && (
                                    <Button className="w-full" variant="outline" onClick={() => switchPlan(plan)}>
                                        Switch to {plan.name}
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    );
                })}
            </div>

            <Card className="mt-6">
                <CardHeader><CardTitle>Invoices</CardTitle></CardHeader>
                <CardContent>
                    {invoices.length === 0 ? (
                        <p className="text-sm text-muted-foreground">No invoices yet.</p>
                    ) : (
                        <ul className="divide-y divide-border text-sm">
                            {invoices.map((inv) => (
                                <li key={inv.id} className="flex items-center justify-between py-2">
                                    <span>{inv.number}</span>
                                    <span>${(inv.amount / 100).toFixed(2)}</span>
                                    <Badge variant={inv.status === 'paid' ? 'good' : 'warn'}>{inv.status}</Badge>
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>

            <Dialog open={!!pendingPlan} onOpenChange={(open) => !open && setPendingPlan(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Choose a payment provider</DialogTitle>
                        <DialogDescription>
                            You'll be redirected to {pendingPlan && PROVIDERS.find((p) => p.value === provider)?.label} to complete payment for the {pendingPlan?.name} plan.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-2">
                        {PROVIDERS.map((p) => (
                            <label key={p.value} className="flex items-center gap-2 rounded-md border border-input p-2 text-sm">
                                <input type="radio" name="provider" checked={provider === p.value} onChange={() => setProvider(p.value)} />
                                {p.label}
                            </label>
                        ))}
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setPendingPlan(null)}>Cancel</Button>
                        <Button onClick={confirmCheckout}>Continue to checkout</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
