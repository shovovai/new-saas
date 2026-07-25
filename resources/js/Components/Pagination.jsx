import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

export default function Pagination({ paginator }) {
    const links = paginator?.links ?? [];

    if (links.length <= 3) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center gap-1 pt-4">
            {links.map((link, index) => (
                <Link
                    key={index}
                    href={link.url ?? '#'}
                    preserveScroll
                    className={cn(
                        'rounded-md px-3 py-1.5 text-sm',
                        link.active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent',
                        !link.url && 'pointer-events-none opacity-40',
                    )}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                />
            ))}
        </div>
    );
}
