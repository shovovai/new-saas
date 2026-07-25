import { Lock } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import ScoreRing from '@/Components/ScoreRing';
import { cn } from '@/lib/utils';

/**
 * One of the 8 dashboard score cards (UIUX §4). Locked state renders a
 * single CTA overlay rather than a dead/disabled card.
 */
export default function ScoreCard({ title, score, suffix, locked, lockedHref, lockedLabel, textValue }) {
    return (
        <Card className="relative overflow-hidden">
            <CardHeader className="pb-2">
                <CardTitle>{title}</CardTitle>
            </CardHeader>
            <CardContent className="flex items-center justify-between">
                {textValue !== undefined ? (
                    <span
                        className={cn(
                            'truncate text-2xl font-semibold capitalize',
                            locked && 'text-muted-foreground',
                        )}
                        title={typeof textValue === 'string' ? textValue.replace(/_/g, ' ') : undefined}
                    >
                        {locked ? '—' : typeof textValue === 'string' ? textValue.replace(/_/g, ' ') : textValue}
                    </span>
                ) : (
                    <ScoreRing score={locked ? null : score} />
                )}
                {suffix && !locked && <span className="text-xs text-muted-foreground">{suffix}</span>}
            </CardContent>

            {locked && (
                <div className="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-background/80 p-4 text-center backdrop-blur-sm">
                    <Lock className="h-5 w-5 text-muted-foreground" />
                    {lockedHref ? (
                        <Link href={lockedHref} className="text-xs font-medium text-primary hover:underline">
                            {lockedLabel ?? 'Verify this website to unlock'}
                        </Link>
                    ) : (
                        <span className="text-xs text-muted-foreground">{lockedLabel ?? 'Locked'}</span>
                    )}
                </div>
            )}
        </Card>
    );
}
