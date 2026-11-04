import { useEffect, useRef, useState } from 'react';
import { request } from '../../lib/http';
import type { SectionData } from '../../lib/types';
import { cn, useT } from '../../lib/utils';
import { Segmented } from '../ui/Form';
import { Icon } from '../ui/Icon';

/** Width the desktop preview is laid out at, so the site shows its desktop layout. */
const DESKTOP_WIDTH = 1280;

/**
 * The page rendered by the site itself from the editor's unsaved state, refreshed shortly
 * after each change. Clicking a section (or a marked field) in it selects it in the editor.
 * The desktop view is laid out at a desktop width and scaled down to fit the panel; the
 * panel can also be opened full screen.
 */
export function LivePreview({
    url,
    document,
    locale,
    onSelect,
}: {
    url: string;
    document: { sections: SectionData[]; [key: string]: unknown };
    locale: string;
    onSelect: (sectionId: string, field: string | null) => void;
}) {
    const t = useT();
    const frame = useRef<HTMLIFrameElement>(null);
    const scrollY = useRef(0);
    const [html, setHtml] = useState<string | null>(null);
    const [state, setState] = useState<'loading' | 'ready' | 'error'>('loading');
    const [device, setDevice] = useState<'desktop' | 'mobile'>('desktop');
    const [expanded, setExpanded] = useState(false);
    const viewport = useRef<HTMLDivElement>(null);
    const [size, setSize] = useState({ width: 0, height: 0 });

    useEffect(() => {
        const element = viewport.current;

        if (!element) {
            return;
        }

        const observer = new ResizeObserver(([entry]) => setSize({ width: entry.contentRect.width, height: entry.contentRect.height }));
        observer.observe(element);

        return () => observer.disconnect();
    }, []);

    useEffect(() => {
        if (!expanded) {
            return;
        }

        const onKey = (event: KeyboardEvent) => event.key === 'Escape' && setExpanded(false);
        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [expanded]);

    const frameWidth = Math.max(DESKTOP_WIDTH, size.width);
    const scale = size.width > 0 ? size.width / frameWidth : 1;

    useEffect(() => {
        const controller = new AbortController();
        const timer = window.setTimeout(async () => {
            setState('loading');
            scrollY.current = frame.current?.contentWindow?.scrollY ?? scrollY.current;

            try {
                const response = await request(url, { method: 'POST', body: { document, locale }, accept: 'text/html', signal: controller.signal });

                if (!response.ok) {
                    throw new Error(String(response.status));
                }

                setHtml(await response.text());
                setState('ready');
            } catch (error) {
                if ((error as Error).name !== 'AbortError') {
                    setState('error');
                }
            }
        }, 450);

        return () => {
            controller.abort();
            window.clearTimeout(timer);
        };
    }, [url, document, locale]);

    useEffect(() => {
        const onMessage = (event: MessageEvent) => {
            if (event.source !== frame.current?.contentWindow || event.data?.type !== 'content:select') {
                return;
            }

            onSelect(String(event.data.section), event.data.field ? String(event.data.field) : null);
        };

        window.addEventListener('message', onMessage);

        return () => window.removeEventListener('message', onMessage);
    }, [onSelect]);

    return (
        <div
            className={cn(
                'flex min-h-0 flex-col overflow-hidden border border-line bg-panel shadow-panel',
                expanded ? 'fixed inset-0 z-50 rounded-none' : 'h-full min-h-[70vh] rounded-panel',
            )}
        >
            <div className="flex items-center justify-between gap-3 border-b border-line px-3 py-2">
                <span className="flex items-center gap-2 text-[13px] font-semibold text-ink-soft">
                    <span className={cn('size-2 rounded-full', state === 'loading' ? 'animate-pulse bg-warning' : state === 'error' ? 'bg-danger' : 'bg-accent')} aria-hidden="true" />
                    {t('live_preview')}
                </span>
                <div className="flex items-center gap-2">
                    <Segmented
                        value={device}
                        onChange={(value) => setDevice(value as 'desktop' | 'mobile')}
                        options={[
                            { value: 'desktop', label: <Icon name="layout" size={16} />, hint: 'Desktop' },
                            { value: 'mobile', label: <span className="block h-4 w-2.5 rounded-[3px] border-[1.5px] border-current" />, hint: 'Mobile' },
                        ]}
                    />
                    <button
                        type="button"
                        onClick={() => setExpanded((value) => !value)}
                        title={t(expanded ? 'collapse_preview' : 'expand_preview')}
                        aria-label={t(expanded ? 'collapse_preview' : 'expand_preview')}
                        aria-pressed={expanded}
                        className="inline-flex size-10 items-center justify-center rounded-control bg-sunken text-ink-soft transition-colors hover:text-ink"
                    >
                        <Icon name={expanded ? 'collapse' : 'expand'} size={16} />
                    </button>
                </div>
            </div>
            <div ref={viewport} className="relative flex-1 overflow-hidden bg-sunken">
                {state === 'error' && <p className="absolute inset-x-0 top-0 z-10 bg-danger-soft px-3 py-2 text-[13px] text-danger">{t('preview_failed')}</p>}
                {html !== null && (
                    <iframe
                        ref={frame}
                        title={t('live_preview')}
                        srcDoc={html}
                        onLoad={() => frame.current?.contentWindow?.scrollTo(0, scrollY.current)}
                        style={
                            device === 'desktop'
                                ? { width: frameWidth, height: size.height / scale, transform: `scale(${scale})`, transformOrigin: '0 0', left: 0 }
                                : undefined
                        }
                        className={cn('absolute top-0 block border-0 bg-white', device === 'mobile' && 'inset-x-0 mx-auto h-full w-[390px] max-w-full')}
                    />
                )}
            </div>
        </div>
    );
}
