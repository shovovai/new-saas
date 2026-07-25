import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

export default function Dashboard({ stats }) {
    const cards = [
        ['Teams', stats.teams],
        ['Websites', stats.websites],
        ['Verified websites', stats.verified_websites],
        ['Pending verifications', stats.pending_verifications],
        ['Plans', stats.plans],
    ];

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold">Admin Dashboard</h2>}>
            <Head title="Admin Dashboard" />
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                {cards.map(([label, value]) => (
                    <Card key={label}>
                        <CardHeader className="pb-2"><CardTitle>{label}</CardTitle></CardHeader>
                        <CardContent><span className="text-2xl font-semibold">{value}</span></CardContent>
                    </Card>
                ))}
            </div>
        </AdminLayout>
    );
}
