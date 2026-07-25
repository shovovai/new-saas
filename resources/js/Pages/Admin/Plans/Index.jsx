import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import { Fragment } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';

export default function Index({ plans = [], features = [] }) {
    const grouped = features.reduce((acc, f) => {
        (acc[f.category ?? 'other'] ??= []).push(f);
        return acc;
    }, {});

    function isEnabled(plan, featureId) {
        return plan.features.find((f) => f.id === featureId)?.pivot?.enabled ?? false;
    }

    function toggle(plan, feature) {
        router.post(
            route('admin.plans.toggle-feature', plan.id),
            { feature_id: feature.id, enabled: !isEnabled(plan, feature.id) },
            { preserveScroll: true },
        );
    }

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold">Plans &amp; Feature Matrix</h2>}>
            <Head title="Admin — Plans" />

            <Card>
                <CardHeader>
                    <CardTitle className="text-foreground">Feature matrix</CardTitle>
                    <CardDescription>This table IS the plan_features data — edits save immediately, no deploy required.</CardDescription>
                </CardHeader>
                <CardContent className="overflow-x-auto">
                    <table className="w-full min-w-[600px] text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase text-muted-foreground">
                                <th className="py-2 pr-4">Feature</th>
                                {plans.map((p) => (
                                    <th key={p.id} className="px-3 py-2 text-center">{p.name}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {Object.entries(grouped).map(([category, feats]) => (
                                <Fragment key={category}>
                                    <tr>
                                        <td colSpan={plans.length + 1} className="pt-4 pb-1 text-xs font-semibold uppercase text-muted-foreground">
                                            {category}
                                        </td>
                                    </tr>
                                    {feats.map((feature) => (
                                        <tr key={feature.id} className="border-t border-border">
                                            <td className="py-2 pr-4">{feature.name}</td>
                                            {plans.map((plan) => (
                                                <td key={plan.id} className="px-3 py-2 text-center">
                                                    <input
                                                        type="checkbox"
                                                        checked={isEnabled(plan, feature.id)}
                                                        onChange={() => toggle(plan, feature)}
                                                        className="h-4 w-4 rounded border-input"
                                                    />
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </Fragment>
                            ))}
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {plans.map((plan) => (
                    <Card key={plan.id}>
                        <CardHeader><CardTitle className="text-foreground">{plan.name}</CardTitle></CardHeader>
                        <CardContent className="space-y-1 text-sm text-muted-foreground">
                            <p>Max websites: {plan.max_websites}</p>
                            <p>Max team members: {plan.max_team_members}</p>
                            <p>Scans/month: {plan.max_scans_per_month}</p>
                            <p>Scan frequency: {plan.scan_frequency}</p>
                            <p>AI credits: {plan.ai_credits}</p>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AdminLayout>
    );
}
