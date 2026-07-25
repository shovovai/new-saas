import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Switch } from '@/Components/ui/switch';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';

const ALERT_LABELS = {
    site_down: 'Site goes down',
    ssl_expiring: 'SSL certificate expiring',
    domain_expiring: 'Domain expiring',
    scan_failed: 'Scan failed',
    scan_completed: 'Scan completed',
    pentest_completed: 'Pen test completed',
};

export default function Index({ preferences = [], availableChannels = [], destinations = {} }) {
    const { data, setData, put, processing } = useForm({
        slack_webhook_url: destinations.slack_webhook_url ?? '',
        discord_webhook_url: destinations.discord_webhook_url ?? '',
        telegram_chat_id: destinations.telegram_chat_id ?? '',
        phone_number: destinations.phone_number ?? '',
        webhook_url: destinations.webhook_url ?? '',
    });

    function toggleChannel(pref, channel) {
        const channels = pref.channels.includes(channel)
            ? pref.channels.filter((c) => c !== channel)
            : [...pref.channels, channel];

        router.put(route('alerts.update'), { alert_type: pref.alert_type, channels, enabled: pref.enabled }, { preserveScroll: true });
    }

    function toggleEnabled(pref) {
        router.put(route('alerts.update'), { alert_type: pref.alert_type, channels: pref.channels, enabled: !pref.enabled }, { preserveScroll: true });
    }

    function saveDestinations(e) {
        e.preventDefault();
        put(route('alerts.update-destinations'), { preserveScroll: true });
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Alerts</h2>}>
            <Head title="Alerts" />

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle className="text-foreground">Channel destinations</CardTitle>
                    <CardDescription>Set these once — they apply to every alert type below where that channel is checked.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={saveDestinations} className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="slack">Slack incoming webhook URL</Label>
                            <Input id="slack" value={data.slack_webhook_url} onChange={(e) => setData('slack_webhook_url', e.target.value)} placeholder="https://hooks.slack.com/services/..." />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="discord">Discord webhook URL</Label>
                            <Input id="discord" value={data.discord_webhook_url} onChange={(e) => setData('discord_webhook_url', e.target.value)} placeholder="https://discord.com/api/webhooks/..." />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="telegram">Telegram chat ID</Label>
                            <Input id="telegram" value={data.telegram_chat_id} onChange={(e) => setData('telegram_chat_id', e.target.value)} placeholder="123456789" />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="phone">Phone number (SMS)</Label>
                            <Input id="phone" value={data.phone_number} onChange={(e) => setData('phone_number', e.target.value)} placeholder="+15551234567" />
                        </div>
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="webhook">Generic webhook URL</Label>
                            <Input id="webhook" value={data.webhook_url} onChange={(e) => setData('webhook_url', e.target.value)} placeholder="https://example.com/hooks/siteguardian" />
                        </div>
                        <Button type="submit" disabled={processing} className="sm:col-span-2 sm:w-fit">Save destinations</Button>
                    </form>
                </CardContent>
            </Card>

            <div className="space-y-4">
                {preferences.map((pref) => (
                    <Card key={pref.alert_type}>
                        <CardHeader className="flex-row items-center justify-between space-y-0">
                            <CardTitle className="text-foreground">{ALERT_LABELS[pref.alert_type] ?? pref.alert_type}</CardTitle>
                            <Switch checked={pref.enabled} onCheckedChange={() => toggleEnabled(pref)} />
                        </CardHeader>
                        <CardContent className="flex flex-wrap gap-3">
                            {availableChannels.map((channel) => (
                                <label key={channel} className="flex items-center gap-1.5 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={pref.channels.includes(channel)}
                                        onChange={() => toggleChannel(pref, channel)}
                                        className="rounded border-input"
                                    />
                                    {channel}
                                </label>
                            ))}
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AuthenticatedLayout>
    );
}
