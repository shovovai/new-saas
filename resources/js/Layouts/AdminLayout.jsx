import { Link } from '@inertiajs/react';
import {
    Bell,
    FileText,
    Flag,
    Key,
    LayoutDashboard,
    LifeBuoy,
    Receipt,
    ScrollText,
    Settings,
    ShieldQuestion,
    Sliders,
    Tag,
    UserCog,
    Users,
} from 'lucide-react';
import FlashBanner from '@/Components/FlashBanner';
import ThemeToggle from '@/Components/ThemeToggle';
import { cn } from '@/lib/utils';

const NAV = [
    { label: 'Dashboard', icon: LayoutDashboard, href: () => route('admin.dashboard'), match: 'admin.dashboard' },
    { label: 'Users', icon: Users, href: () => route('admin.users.index'), match: 'admin.users.*' },
    { label: 'Roles & Permissions', icon: UserCog, href: () => route('admin.permissions.index'), match: 'admin.permissions.*' },
    { label: 'Plans', icon: Sliders, href: () => route('admin.plans.index'), match: 'admin.plans.*' },
    { label: 'Feature Flags', icon: Flag, href: () => route('admin.feature-flags.index'), match: 'admin.feature-flags.*' },
    { label: 'Verification Requests', icon: ShieldQuestion, href: () => route('admin.verification-requests.index'), match: 'admin.verification-requests.*' },
    { label: 'Coupons', icon: Tag, href: () => route('admin.coupons.index'), match: 'admin.coupons.*' },
    { label: 'Invoices', icon: FileText, href: () => route('admin.invoices.index'), match: 'admin.invoices.*' },
    { label: 'Payments', icon: Receipt, href: () => route('admin.payments.index'), match: 'admin.payments.*' },
    { label: 'API Keys', icon: Key, href: () => route('admin.api-keys.index'), match: 'admin.api-keys.*' },
    { label: 'Announcements', icon: Bell, href: () => route('admin.announcements.index'), match: 'admin.announcements.*' },
    { label: 'Support Tickets', icon: LifeBuoy, href: () => route('admin.support-tickets.index'), match: 'admin.support-tickets.*' },
    { label: 'Settings', icon: Settings, href: () => route('admin.settings.index'), match: 'admin.settings.*' },
    { label: 'Logs', icon: ScrollText, href: () => route('admin.logs.index'), match: 'admin.logs.*' },
];

export default function AdminLayout({ header, children }) {
    return (
        <div className="flex h-screen flex-col bg-background text-foreground">
            <header className="flex h-14 shrink-0 items-center gap-4 border-b border-border bg-card px-4">
                <span className="font-semibold">SiteGuardian AI — Admin</span>
                <Link href={route('dashboard')} className="text-sm text-muted-foreground hover:underline">
                    ← Back to app
                </Link>
                <div className="ml-auto"><ThemeToggle /></div>
            </header>

            <div className="flex flex-1 overflow-hidden">
                <nav className="flex w-60 shrink-0 flex-col gap-0.5 overflow-y-auto border-r border-border bg-card px-3 py-4">
                    {NAV.map((item) => {
                        const Icon = item.icon;
                        const active = route().current(item.match);
                        return (
                            <Link
                                key={item.label}
                                href={item.href()}
                                className={cn(
                                    'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                    active ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                                )}
                            >
                                <Icon className="h-4 w-4 shrink-0" />
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>

                <div className="flex flex-1 flex-col overflow-y-auto">
                    <FlashBanner />
                    {header && <div className="border-b border-border bg-card/50 px-6 py-4">{header}</div>}
                    <main className="flex-1 p-6">{children}</main>
                </div>
            </div>
        </div>
    );
}
