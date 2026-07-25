import { cn, scoreTone } from '@/lib/utils';

const TONE_STROKE = {
    good: 'stroke-score-good',
    warn: 'stroke-score-warn',
    critical: 'stroke-score-critical',
    locked: 'stroke-score-locked',
};

export default function ScoreRing({ score = null, size = 72, strokeWidth = 6, className }) {
    const tone = scoreTone(score);
    const radius = (size - strokeWidth) / 2;
    const circumference = 2 * Math.PI * radius;
    const clamped = Math.min(100, Math.max(0, score ?? 0));
    const offset = circumference - (clamped / 100) * circumference;

    return (
        <div className={cn('relative inline-flex items-center justify-center', className)} style={{ width: size, height: size }}>
            <svg width={size} height={size} className="-rotate-90">
                <circle
                    cx={size / 2}
                    cy={size / 2}
                    r={radius}
                    strokeWidth={strokeWidth}
                    className="fill-none stroke-muted"
                />
                {score !== null && (
                    <circle
                        cx={size / 2}
                        cy={size / 2}
                        r={radius}
                        strokeWidth={strokeWidth}
                        strokeLinecap="round"
                        strokeDasharray={circumference}
                        strokeDashoffset={offset}
                        style={{ '--score-circumference': circumference, '--score-offset': offset }}
                        className={cn('fill-none animate-score-fill', TONE_STROKE[tone])}
                    />
                )}
            </svg>
            <span className="absolute text-sm font-semibold tabular-nums">
                {score !== null ? Math.round(score) : '—'}
            </span>
        </div>
    );
}
