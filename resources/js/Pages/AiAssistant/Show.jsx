import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { cn } from '@/lib/utils';

const PROMPT_CHIPS = [
    'Why is my site slow?',
    'What should I fix first?',
    'Generate an Nginx fix',
];

export default function Show({ website, available, messages = [], recentReports = {} }) {
    const { data, setData, post, processing, reset } = useForm({ message: '' });

    function send(message) {
        if (!message.trim()) return;
        router.post(route('ai.store', website.id), { message }, {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    }

    if (!available) {
        return (
            <AuthenticatedLayout header={<h2 className="text-xl font-semibold">AI Assistant</h2>}>
                <Head title="AI Assistant" />
                <Card className="mx-auto max-w-lg">
                    <CardContent className="flex flex-col items-center gap-3 p-10 text-center">
                        <Sparkles className="h-8 w-8 text-muted-foreground" />
                        <p className="text-sm text-muted-foreground">
                            {website.status !== 'verified'
                                ? 'Verify this website to unlock the AI Assistant.'
                                : 'The AI Assistant is not included in your current plan.'}
                        </p>
                    </CardContent>
                </Card>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold">AI Assistant — {website.domain}</h2>}>
            <Head title={`AI Assistant — ${website.domain}`} />

            <div className="grid gap-6 lg:grid-cols-[1fr_260px]">
                <Card className="flex h-[70vh] flex-col">
                    <CardContent className="flex flex-1 flex-col gap-4 overflow-y-auto p-4">
                        {messages.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                Ask anything about {website.domain}'s performance, SEO, security, or accessibility.
                            </p>
                        )}
                        {messages.map((m) => (
                            <div
                                key={m.id}
                                className={cn(
                                    'max-w-[80%] rounded-lg px-3 py-2 text-sm whitespace-pre-wrap',
                                    m.role === 'assistant' ? 'self-start bg-muted' : 'self-end bg-primary text-primary-foreground',
                                )}
                            >
                                {m.content}
                            </div>
                        ))}
                    </CardContent>

                    <div className="border-t border-border p-4">
                        <div className="mb-3 flex flex-wrap gap-2">
                            {PROMPT_CHIPS.map((chip) => (
                                <button
                                    key={chip}
                                    onClick={() => send(chip)}
                                    className="rounded-full border border-input px-3 py-1 text-xs hover:bg-accent"
                                >
                                    {chip}
                                </button>
                            ))}
                        </div>
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                send(data.message);
                            }}
                            className="flex gap-2"
                        >
                            <Input
                                value={data.message}
                                onChange={(e) => setData('message', e.target.value)}
                                placeholder="Ask a question…"
                            />
                            <Button type="submit" disabled={processing}>Send</Button>
                        </form>
                    </div>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Recent reports</CardTitle></CardHeader>
                    <CardContent className="space-y-3">
                        {Object.entries(recentReports).map(([type, reports]) => (
                            <div key={type}>
                                <p className="text-xs font-medium uppercase text-muted-foreground">{type}</p>
                                {reports.length === 0 ? (
                                    <p className="text-xs text-muted-foreground">No scans yet</p>
                                ) : (
                                    reports.map((r) => (
                                        <div key={r.id} className="flex items-center justify-between text-xs">
                                            <span>{new Date(r.created_at).toLocaleDateString()}</span>
                                            <Badge variant="outline">{r.score ?? '—'}</Badge>
                                        </div>
                                    ))
                                )}
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
