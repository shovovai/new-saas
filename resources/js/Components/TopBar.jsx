import { Link, usePage } from '@inertiajs/react';
import { Bell, ChevronDown } from 'lucide-react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import ThemeToggle from '@/Components/ThemeToggle';
import { Badge } from '@/Components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';

export default function TopBar({ activeWebsiteId, onSelectWebsite }) {
    const { auth, team, plan, websites = [] } = usePage().props;

    const initials = (auth.user.name || '?')
        .split(' ')
        .map((p) => p[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

    return (
        <header className="flex h-14 shrink-0 items-center gap-4 border-b border-border bg-card px-4">
            <Link href={route('dashboard')} className="flex items-center gap-2 font-semibold">
                <ApplicationLogo className="h-6 w-6 fill-current text-primary" />
                <span className="hidden sm:inline">SiteGuardian AI</span>
            </Link>

            {websites.length > 0 && (
                <DropdownMenu>
                    <DropdownMenuTrigger className="flex items-center gap-1 rounded-md border border-input px-3 py-1.5 text-sm hover:bg-accent">
                        {websites.find((w) => w.id === activeWebsiteId)?.domain ?? 'Select a website'}
                        <ChevronDown className="h-3.5 w-3.5 opacity-60" />
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start">
                        <DropdownMenuLabel>Your websites</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        {websites.map((site) => (
                            <DropdownMenuItem key={site.id} onSelect={() => onSelectWebsite(site.id)}>
                                {site.domain}
                                {site.status !== 'verified' && (
                                    <Badge variant="locked" className="ml-auto">unverified</Badge>
                                )}
                            </DropdownMenuItem>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            )}

            <div className="ml-auto flex items-center gap-2">
                {plan && <Badge variant="secondary">{plan.name} plan</Badge>}

                <ThemeToggle />

                <button className="rounded-md p-2 text-muted-foreground hover:bg-accent hover:text-accent-foreground" aria-label="Notifications">
                    <Bell className="h-4 w-4" />
                </button>

                <DropdownMenu>
                    <DropdownMenuTrigger>
                        <Avatar className="h-8 w-8">
                            <AvatarFallback>{initials}</AvatarFallback>
                        </Avatar>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuLabel>{auth.user.name}</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link href={route('profile.edit')}>Profile</Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link href={route('logout')} method="post" as="button" className="w-full text-left">
                                Log out
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}
