import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { usePage } from '@inertiajs/react';

export default function Index({ available, keys = [] }) {
    const { data, setData, post, processing, reset } = useForm({ name: '' });
    const { flash } = usePage().props;

    function submit(e) {
        e.preventDefault();
        post(route('api-keys.store'), { onSuccess: () => reset() });
    }

    if (!available) {
        return (
            <AuthenticatedLayout header={<h2 className="text-xl font-semibold">API</h2>}>
                <Head title="API" />
                <Card className="mx-auto max-w-lg">
                    <CardContent className="flex flex-col items-center gap-3 p-10 text-center">
                        <KeyRound className="h-8 w-8 text-muted-foreground" />
                        <p className="text-sm text-muted-foreground">API access is not included in your current plan.</p>
                    </CardContent>
                </Card>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold">API Keys</h2>}>
            <Head title="API Keys" />

            {flash?.plaintextKey && (
                <div className="mb-4 rounded-lg border border-score-good/30 bg-score-good/10 p-4 text-sm">
                    Copy this key now — it won't be shown again:
                    <pre className="mt-2 rounded bg-muted p-2 font-mono text-xs">{flash.plaintextKey}</pre>
                </div>
            )}

            <Card className="mb-6">
                <CardHeader><CardTitle>Create a new key</CardTitle></CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="flex gap-2">
                        <Input placeholder="Key name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        <Button type="submit" disabled={processing}>Generate</Button>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>Your keys</CardTitle></CardHeader>
                <CardContent>
                    {keys.length === 0 ? (
                        <p className="text-sm text-muted-foreground">No API keys yet.</p>
                    ) : (
                        <ul className="divide-y divide-border text-sm">
                            {keys.map((k) => (
                                <li key={k.id} className="flex items-center justify-between py-2">
                                    <div>
                                        <span className="font-medium">{k.name}</span>
                                        <span className="ml-2 font-mono text-xs text-muted-foreground">{k.key_prefix}…</span>
                                    </div>
                                    {k.revoked_at ? (
                                        <Badge variant="locked">Revoked</Badge>
                                    ) : (
                                        <Button size="sm" variant="destructive" onClick={() => router.delete(route('api-keys.destroy', k.id))}>
                                            Revoke
                                        </Button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
