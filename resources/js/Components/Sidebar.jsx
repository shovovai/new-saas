import { Link, usePage } from '@inertiajs/react';
import {
    Activity,
    Bell,
    Bug,
    CreditCard,
    FileText,
    Gauge,
    Globe,
    KeyRound,
    LayoutDashboard,
    LifeBuoy,
    Search,
    Settings,
    ShieldCheck,
    Sparkles,
    Users,
} from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * Rendered dynamically (UIUX §2): items are hidden entirely — not just
 * disabled — when the team's plan excludes that feature.
 */
function navItems(activeWebsiteId) {
    return [
        { label: 'Dashboard', icon: LayoutDashboard, href: route('dashboard'), match: 'dashboard' },
        { label: 'Websites', icon: Globe, href: route('websites.index'), match: 'websites.*' },
        {
            label: 'AI Assistant', icon: Sparkles, feature: 'ai.assistant',
            href: activeWebsiteId ? route('ai.show', activeWebsiteId) : route('websites.index'),
            match: 'ai.*',
        },
        {
            label: 'Monitoring', icon: Activity, feature: 'monitoring.core',
            href: activeWebsiteId ? route('monitoring.show', activeWebsiteId) : route('websites.index'),
            match: 'monitoring.*',
        },
        {
            label: 'Performance', icon: Gauge, feature: 'performance.scans',
            href: activeWebsiteId ? route('scans.show', [activeWebsiteId, 'performance']) : route('websites.index'),
        },
        {
            label: 'SEO', icon: Search, feature: 'seo.scans',
            href: activeWebsiteId ? route('scans.show', [activeWebsiteId, 'seo']) : route('websites.index'),
        },
        {
            label: 'Security', icon: ShieldCheck, feature: 'security.scans',
            href: activeWebsiteId ? route('scans.show', [activeWebsiteId, 'security']) : route('websites.index'),
        },
        {
            label: 'Pen Testing', icon: Bug, feature: 'pentest.module',
            href: activeWebsiteId ? route('pentest.show', activeWebsiteId) : route('websites.index'),
            match: 'pentest.*',
        },
        { label: 'Reports', icon: FileText, href: route('reports.index'), match: 'reports.*' },
        { label: 'Alerts', icon: Bell, href: route('alerts.index'), match: 'alerts.*' },
        { label: 'Team', icon: Users, href: route('team.show'), match: 'team.*' },
        { label: 'Billing', icon: CreditCard, href: route('billing.show'), match: 'billing.*' },
        { label: 'Settings', icon: Settings, href: route('profile.edit'), match: 'profile.*' },
        { label: 'API', icon: KeyRound, feature: 'api.access', href: route('api-keys.index'), match: 'api-keys.*' },
        { label: 'Support', icon: LifeBuoy, href: route('support.index'), match: 'support.*' },
    ];
}

export default function Sidebar({ activeWebsiteId }) {
    const { enabledFeatures = [] } = usePage().props;

    const items = navItems(activeWebsiteId).filter(
        (item) => !item.feature || enabledFeatures.includes(item.feature),
    );

    return (
        <nav className="flex h-full w-60 shrink-0 flex-col gap-0.5 overflow-y-auto border-r border-border bg-card px-3 py-4">
            {items.map((item) => {
                const isActive = item.match ? route().current(item.match) : route().current() === item.href;
                const Icon = item.icon;

                return (
                    <Link
                        key={item.label}
                        href={item.href}
                        className={cn(
                            'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            isActive
                                ? 'bg-primary/10 text-primary'
                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                        )}
                    >
                        <Icon className="h-4 w-4 shrink-0" />
                        {item.label}
                    </Link>
                );
            })}
        </nav>
    );
}
