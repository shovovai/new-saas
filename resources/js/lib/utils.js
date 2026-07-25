import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs) {
    return twMerge(clsx(inputs));
}

export function scoreTone(score) {
    if (score === null || score === undefined) return 'locked';
    if (score >= 90) return 'good';
    if (score >= 50) return 'warn';
    return 'critical';
}
