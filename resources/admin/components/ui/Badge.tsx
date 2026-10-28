import type { ReactNode } from 'react';
import { cn } from '../../lib/utils';

const TONES = {
    success: 'bg-accent-soft text-accent-strong',
    warning: 'bg-warning-soft text-warning',
    neutral: 'bg-sunken text-ink-soft',
    danger: 'bg-danger-soft text-danger',
    info: 'bg-info-soft text-info',
};

export function Badge({ tone = 'neutral', children, dot = false }: { tone?: keyof typeof TONES; children: ReactNode; dot?: boolean }) {
    return (
        <span className={cn('inline-flex h-6 items-center gap-1.5 rounded-full px-2.5 text-xs font-semibold whitespace-nowrap', TONES[tone])}>
            {dot && <span className="size-1.5 rounded-full bg-current" aria-hidden="true" />}
            {children}
        </span>
    );
}
