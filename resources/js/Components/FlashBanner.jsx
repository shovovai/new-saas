import { usePage } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';

export default function FlashBanner() {
    const { flash } = usePage().props;
    const [dismissed, setDismissed] = useState({});

    useEffect(() => {
        setDismissed({});
    }, [flash?.success, flash?.error]);

    const messages = [
        flash?.success && { type: 'success', text: flash.success },
        flash?.error && { type: 'error', text: flash.error },
    ].filter(Boolean);

    if (messages.length === 0) return null;

    return (
        <div className="flex flex-col gap-2 px-6 pt-4">
            {messages.map((m, i) =>
                dismissed[i] ? null : (
                    <div
                        key={i}
                        className={cn(
                            'flex items-center gap-2 rounded-lg border px-4 py-2.5 text-sm',
                            m.type === 'success'
                                ? 'border-score-good/30 bg-score-good/10 text-score-good'
                                : 'border-score-critical/30 bg-score-critical/10 text-score-critical',
                        )}
                    >
                        {m.type === 'success' ? <CheckCircle2 className="h-4 w-4 shrink-0" /> : <AlertTriangle className="h-4 w-4 shrink-0" />}
                        <span className="flex-1">{m.text}</span>
                        <button onClick={() => setDismissed((d) => ({ ...d, [i]: true }))} aria-label="Dismiss">
                            <X className="h-4 w-4 opacity-60 hover:opacity-100" />
                        </button>
                    </div>
                ),
            )}
        </div>
    );
}
