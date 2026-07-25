import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { Globe, Plus } from 'lucide-react';
import ScoreCard from '@/Components/ScoreCard';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import useActiveWebsite from '@/hooks/useActiveWebsite';

export default function Dashboard({ websites = [] }) {
    const { enabledFeatures = [] } = usePage().props;
    const [activeWebsiteId] = useActiveWebsite();
    const website = websites.find((w) => w.id === activeWebsiteId) ?? websites[0];

    if (websites.length === 0) {
        return (
            <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Dashboard</h2>}>
                <Head title="Dashboard" />
                <div className="flex flex-col items-center justify-center gap-4 rounded-xl border border-dashed border-border py-24 text-center">
                    <Globe className="h-10 w-10 text-muted-foreground" />
                    <div>
                        <p className="text-lg font-medium">No websites yet</p>
                        <p className="text-sm text-muted-foreground">Add your first website to start monitoring it.</p>
                    </div>
                    <Button asChild>
                        <Link href={route('websites.create')}>
                            <Plus className="h-4 w-4" /> Add website
                        </Link>
                    </Button>
                </div>
            </AuthenticatedLayout>
        );
    }

    const locked = !website?.verified;
    const scores = website?.scores ?? {};
    const verifyHref = website ? route('websites.show', website.id) : undefined;

    const numericScores = ['performance', 'seo', 'security', 'accessibility']
        .map((k) => scores[k])
        .filter((v) => typeof v === 'number');
    const health = numericScores.length ? Math.round(numericScores.reduce((a, b) => a + b, 0) / numericScores.length) : null;

    const cards = [
        { key: 'health', title: 'Website Health', score: health, feature: null },
        { key: 'performance', title: 'Performance Score', score: scores.performance, feature: 'performance.scans' },
        { key: 'seo', title: 'SEO Score', score: scores.seo, feature: 'seo.scans' },
        { key: 'security', title: 'Security Score', score: scores.security, feature: 'security.scans' },
        { key: 'accessibility', title: 'Accessibility Score', score: scores.accessibility, feature: 'accessibility.scans' },
    ].filter((c) => !c.feature || enabledFeatures.includes(c.feature));

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Dashboard</h2>}>
            <Head title="Dashboard" />

            <div className="mb-4 flex items-center gap-2">
                <h3 className="text-lg font-medium">{website?.name}</h3>
                <Badge variant={website?.verified ? 'good' : 'locked'}>{website?.status}</Badge>
            </div>

            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                {cards.map((c) => (
                    <ScoreCard
                        key={c.key}
                        title={c.title}
                        score={c.score}
                        locked={locked}
                        lockedHref={verifyHref}
                    />
                ))}

                <ScoreCard
                    title="SSL Status"
                    textValue={scores.ssl_status ?? 'Unknown'}
                    locked={locked}
                    lockedHref={verifyHref}
                />
                <ScoreCard
                    title="Website Status"
                    textValue={website?.status}
                    locked={false}
                />
                <ScoreCard
                    title="Domain Expiry"
                    textValue={scores.domain_expiry_days ? `${scores.domain_expiry_days}d` : 'Unknown'}
                    locked={locked}
                    lockedHref={verifyHref}
                />
            </div>

            {locked && (
                <div className="mt-6 rounded-lg border border-score-locked/30 bg-score-locked/10 p-4 text-sm">
                    Verify this website to unlock monitoring, scans, and AI analysis.{' '}
                    <Link href={verifyHref} className="font-medium text-primary hover:underline">
                        Verify now →
                    </Link>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
