import { usePage } from '@inertiajs/react';
import { Megaphone, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';

const SEVERITY_STYLE = {
    info: 'border-primary/30 bg-primary/10 text-primary',
    warning: 'border-score-warn/30 bg-score-warn/10 text-score-warn',
    critical: 'border-score-critical/30 bg-score-critical/10 text-score-critical',
};

export default function AnnouncementBanner() {
    const { announcement } = usePage().props;
    const [dismissedId, setDismissedId] = useState(null);

    useEffect(() => {
        setDismissedId(null);
    }, [announcement?.id]);

    if (!announcement || announcement.id === dismissedId) return null;

    return (
        <div className="px-6 pt-4">
            <div className={cn('flex items-center gap-2 rounded-lg border px-4 py-2.5 text-sm', SEVERITY_STYLE[announcement.severity] ?? SEVERITY_STYLE.info)}>
                <Megaphone className="h-4 w-4 shrink-0" />
                <span className="flex-1"><strong>{announcement.title}</strong> — {announcement.body}</span>
                <button onClick={() => setDismissedId(announcement.id)} aria-label="Dismiss">
                    <X className="h-4 w-4 opacity-60 hover:opacity-100" />
                </button>
            </div>
        </div>
    );
}
