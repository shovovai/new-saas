import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle2, Clock, Copy, ShieldCheck, XCircle } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';

const METHOD_LABELS = {
    dns_txt: 'DNS TXT Record',
    html_file: 'HTML File',
    meta_tag: 'Meta Tag',
};

function CopyableBlock({ text }) {
    const [copied, setCopied] = useState(false);

    return (
        <div className="relative rounded-md border border-border bg-muted/50 p-3 font-mono text-xs">
            <pre className="whitespace-pre-wrap pr-8">{text}</pre>
            <button
                className="absolute right-2 top-2 rounded p-1 text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                onClick={() => {
                    navigator.clipboard.writeText(text);
                    setCopied(true);
                    setTimeout(() => setCopied(false), 1500);
                }}
                aria-label="Copy to clipboard"
            >
                {copied ? <CheckCircle2 className="h-4 w-4 text-score-good" /> : <Copy className="h-4 w-4" />}
            </button>
        </div>
    );
}

export default function Show({ website, verificationMethods }) {
    const [verifying, setVerifying] = useState(null);
    const [method, setMethod] = useState(Object.keys(verificationMethods)[0]);

    function verifyNow(selectedMethod) {
        setVerifying(selectedMethod);
        router.post(
            route('websites.verify', website.id),
            { method: selectedMethod },
            { preserveScroll: true, onFinish: () => setVerifying(null) },
        );
    }

    if (website.status === 'verified') {
        return (
            <AuthenticatedLayout header={<h2 className="text-xl font-semibold">{website.name}</h2>}>
                <Head title={website.name} />

                <Card className="mx-auto max-w-2xl">
                    <CardContent className="flex flex-col items-center gap-4 p-10 text-center">
                        <ShieldCheck className="h-12 w-12 text-score-good" />
                        <div>
                            <p className="text-lg font-medium">{website.domain} is verified</p>
                            <p className="text-sm text-muted-foreground">
                                Verified via {METHOD_LABELS[website.verified_method] ?? website.verified_method} on{' '}
                                {new Date(website.verified_at).toLocaleDateString()}.
                            </p>
                        </div>
                        <div className="flex flex-wrap justify-center gap-2">
                            <Button asChild variant="outline">
                                <Link href={route('scans.show', [website.id, 'performance'])}>Performance</Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={route('scans.show', [website.id, 'seo'])}>SEO</Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={route('scans.show', [website.id, 'security'])}>Security</Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={route('ai.show', website.id)}>AI Assistant</Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={route('pentest.show', website.id)}>Pen Testing</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </AuthenticatedLayout>
        );
    }

    const active = verificationMethods[method];

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Verify {website.domain}</h2>}>
            <Head title={`Verify ${website.domain}`} />

            <div className="mx-auto max-w-3xl space-y-6">
                <div className="flex items-center gap-2 rounded-lg border border-score-warn/30 bg-score-warn/10 p-4 text-sm">
                    <Clock className="h-4 w-4 shrink-0" />
                    Pending verification — AI, monitoring, pen testing, SEO, performance, and reports are locked for this site
                    until it's verified.
                </div>

                <Tabs value={method} onValueChange={setMethod}>
                    <TabsList className="grid w-full grid-cols-3">
                        {Object.entries(verificationMethods).map(([key, m]) => (
                            <TabsTrigger key={key} value={key} className="flex flex-col gap-0.5 py-2">
                                <span>{METHOD_LABELS[key]}</span>
                                <span className="text-[10px] font-normal text-muted-foreground">~{m.estimated_minutes} min</span>
                            </TabsTrigger>
                        ))}
                    </TabsList>

                    {Object.entries(verificationMethods).map(([key, m]) => (
                        <TabsContent key={key} value={key}>
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base text-foreground">
                                        {m.summary}
                                        {m.status === 'failed' && <Badge variant="critical">Failed</Badge>}
                                    </CardTitle>
                                    <CardDescription>Follow the instructions below, then click Verify Now.</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <CopyableBlock text={m.instructions} />

                                    {m.last_error && (
                                        <div className="flex items-start gap-2 rounded-md border border-score-critical/30 bg-score-critical/10 p-3 text-sm text-score-critical">
                                            <XCircle className="mt-0.5 h-4 w-4 shrink-0" />
                                            <div>
                                                {m.last_error}
                                                {m.attempts > 0 && (
                                                    <div className="mt-1 text-xs opacity-80">{m.attempts} attempt(s) so far.</div>
                                                )}
                                            </div>
                                        </div>
                                    )}

                                    <Button onClick={() => verifyNow(key)} disabled={verifying === key}>
                                        {verifying === key ? 'Verifying…' : 'Verify Now'}
                                    </Button>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    ))}
                </Tabs>
            </div>
        </AuthenticatedLayout>
    );
}
