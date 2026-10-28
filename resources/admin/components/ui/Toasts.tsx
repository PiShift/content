import { useEffect, useState } from 'react';
import { cn, useShared } from '../../lib/utils';
import { Icon } from './Icon';

/**
 * Shows the server's flash messages ("Draft saved", "Page published"…).
 */
export function Toasts() {
    const { flash } = useShared();
    const [visible, setVisible] = useState<{ tone: 'success' | 'error'; text: string; key: number } | null>(null);

    useEffect(() => {
        const text = flash?.error ?? flash?.success;

        if (!text) {
            return;
        }

        setVisible({ tone: flash?.error ? 'error' : 'success', text, key: Date.now() });

        const timer = window.setTimeout(() => setVisible(null), 3500);

        return () => window.clearTimeout(timer);
    }, [flash]);

    if (!visible) {
        return null;
    }

    return (
        <div className="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex justify-center px-4" aria-live="polite">
            <div
                key={visible.key}
                className={cn(
                    'pointer-events-auto flex items-center gap-2.5 rounded-control px-4 py-2.5 text-sm font-medium shadow-pop',
                    visible.tone === 'error' ? 'bg-danger text-white' : 'bg-ink text-white',
                )}
            >
                <Icon name={visible.tone === 'error' ? 'alert' : 'check'} size={17} />
                {visible.text}
            </div>
        </div>
    );
}
