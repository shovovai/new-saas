import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';

export default function Create({ remainingSlots = 0 }) {
    const { data, setData, post, processing, errors } = useForm({ url: '', name: '' });

    function submit(e) {
        e.preventDefault();
        post(route('websites.store'));
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Add Website</h2>}>
            <Head title="Add Website" />

            <Card className="mx-auto max-w-xl">
                <CardHeader>
                    <CardTitle className="text-lg text-foreground">Enter your website URL</CardTitle>
                    <CardDescription>
                        {remainingSlots > 0
                            ? `You can add ${remainingSlots} more website${remainingSlots === 1 ? '' : 's'} on your current plan.`
                            : "You've reached your plan's website limit — upgrading first will unlock this."}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="url">Website URL</Label>
                            <Input
                                id="url"
                                placeholder="https://example.com"
                                value={data.url}
                                onChange={(e) => setData('url', e.target.value)}
                                autoFocus
                            />
                            {errors.url && <p className="text-sm text-destructive">{errors.url}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="name">Display name (optional)</Label>
                            <Input
                                id="name"
                                placeholder="My website"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                            />
                        </div>

                        <Button type="submit" disabled={processing || remainingSlots <= 0} className="w-full">
                            Continue to verification
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
