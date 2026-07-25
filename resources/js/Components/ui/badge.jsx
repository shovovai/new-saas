import * as React from 'react';
import { cva } from 'class-variance-authority';
import { cn } from '@/lib/utils';

export const badgeVariants = cva(
    'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium transition-colors focus:outline-none',
    {
        variants: {
            variant: {
                default: 'border-transparent bg-primary text-primary-foreground',
                secondary: 'border-transparent bg-secondary text-secondary-foreground',
                destructive: 'border-transparent bg-destructive text-destructive-foreground',
                outline: 'text-foreground',
                good: 'border-transparent bg-score-good/15 text-score-good',
                warn: 'border-transparent bg-score-warn/15 text-score-warn',
                critical: 'border-transparent bg-score-critical/15 text-score-critical',
                locked: 'border-transparent bg-score-locked/15 text-score-locked',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    },
);

export function Badge({ className, variant, ...props }) {
    return <div className={cn(badgeVariants({ variant }), className)} {...props} />;
}
