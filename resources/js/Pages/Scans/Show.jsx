import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import ScoreRing from '@/Components/ScoreRing';
import {
    Tooltip, TooltipContent, TooltipProvider, TooltipTrigger,
} from '@/Components/ui/tooltip';

const TYPE_LABELS = {
    performance: 'Performance',
    seo: 'SEO',
    security: 'Security',
    accessibility: 'Accessibility',
};

const SEVERITY_VARIANT = { critical: 'critical', warn: 'warn', info: 'outline' };

export default function Show({ website, type, latest, history = [], canRunScan, quotaRemaining }) {
    const [running, setRunning] = useState(false);

    function scanNow() {
        setRunning(true);
        router.post(route('scans.store', [website.id, type]), {}, {
            preserveScroll: true,
            onFinish: () => setRunning(false),
        });
    }

    const findings = latest?.findings ?? [];

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold">{TYPE_LABELS[type]} — {website.domain}</h2>
                    <TooltipProvider>
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <span>
                                    <Button onClick={scanNow} disabled={!canRunScan || running}>
                                        <RefreshCw className={running ? 'h-4 w-4 animate-spin' : 'h-4 w-4'} />
                                        {running ? 'Scanning…' : 'Scan Now'}
                                    </Button>
                                </span>
                            </TooltipTrigger>
                            {!canRunScan && (
                                <TooltipContent>
                                    {website.status !== 'verified'
                                        ? 'Verify this website to run scans.'
                                        : quotaRemaining <= 0
                                            ? 'Monthly scan quota reached — upgrade your plan.'
                                            : 'This scan type is not included in your current plan.'}
                                </TooltipContent>
                            )}
                        </Tooltip>
                    </TooltipProvider>
                </div>
            }
        >
            <Head title={`${TYPE_LABELS[type]} — ${website.domain}`} />

            <div className="grid gap-6 lg:grid-cols-[200px_1fr]">
                <Card>
                    <CardHeader><CardTitle>Overall score</CardTitle></CardHeader>
                    <CardContent className="flex flex-col items-center gap-2">
                        <ScoreRing score={latest?.score ?? null} size={96} />
                        <p className="text-xs text-muted-foreground">
                            {latest ? `Scanned ${new Date(latest.created_at).toLocaleString()}` : 'Not scanned yet'}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Findings</CardTitle></CardHeader>
                    <CardContent>
                        {!latest ? (
                            <p className="text-sm text-muted-foreground">
                                No scan has run yet for this website. Click "Scan Now" to run the first one.
                            </p>
                        ) : findings.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No issues found. 🎉</p>
                        ) : (
                            <ul className="divide-y divide-border">
                                {findings.map((f, i) => (
                                    <li key={i} className="flex items-start justify-between gap-4 py-3">
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <Badge variant={SEVERITY_VARIANT[f.severity] ?? 'outline'}>{f.severity}</Badge>
                                                <span className="font-medium">{f.title}</span>
                                            </div>
                                            <p className="mt-1 text-sm text-muted-foreground">{f.explanation}</p>
                                        </div>
                                        <Button asChild size="sm" variant="ghost">
                                            <Link href={route('ai.show', website.id)}>Ask AI</Link>
                                        </Button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>

            {history.length > 1 && (
                <Card className="mt-6">
                    <CardHeader><CardTitle>History</CardTitle></CardHeader>
                    <CardContent>
                        <ul className="space-y-1 text-sm">
                            {history.map((r) => (
                                <li key={r.id} className="flex justify-between text-muted-foreground">
                                    <span>{new Date(r.created_at).toLocaleString()}</span>
                                    <span className="font-medium text-foreground">{r.score ?? '—'}</span>
                                </li>
                            ))}
                        </ul>
                    </CardContent>
                </Card>
            )}
        </AuthenticatedLayout>
    );
}
