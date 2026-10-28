import { useCallback, useEffect, useRef, useState, type DragEvent } from 'react';
import { request, requestJson } from '../../lib/http';
import { cn, useT } from '../../lib/utils';
import { Button } from '../ui/Button';
import { Segmented, TextInput } from '../ui/Form';
import { Icon } from '../ui/Icon';
import { Modal } from '../ui/Modal';
import { formatSize, rememberMedia, useMediaConfig, type MediaItem } from './media';

interface Page {
    data: MediaItem[];
    next_page_url: string | null;
}

/**
 * Pick a file from the library, or upload one (drop or browse) and pick it.
 */
export function MediaPicker({ open, onClose, onPick, type }: { open: boolean; onClose: () => void; onPick: (item: MediaItem) => void; type: 'image' | 'file' }) {
    const t = useT();
    const { urls, accept } = useMediaConfig();
    const [filter, setFilter] = useState<string>(type === 'image' ? 'image' : '');
    const [query, setQuery] = useState('');
    const [items, setItems] = useState<MediaItem[]>([]);
    const [next, setNext] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    const load = useCallback(
        async (url?: string) => {
            setLoading(true);
            const params = new URLSearchParams({ q: query, type: filter });
            const page = await requestJson<Page>(url ?? `${urls.list}?${params}`).catch(() => ({ data: [], next_page_url: null }));
            page.data.forEach(rememberMedia);
            setItems((current) => (url ? [...current, ...page.data] : page.data));
            setNext(page.next_page_url);
            setLoading(false);
        },
        [query, filter, urls.list],
    );

    useEffect(() => {
        if (!open) {
            return;
        }

        const timer = window.setTimeout(() => load(), query ? 250 : 0);

        return () => window.clearTimeout(timer);
    }, [open, load, query]);

    return (
        <Modal open={open} onClose={onClose} title={t('choose_media')} size="lg">
            <div className="flex flex-col gap-4">
                <UploadZone
                    accept={type === 'image' ? 'image/*' : accept}
                    onUploaded={(item) => {
                        rememberMedia(item);
                        onPick(item);
                        onClose();
                    }}
                />
                <div className="flex flex-wrap items-center gap-3">
                    <TextInput type="search" value={query} onChange={(event) => setQuery(event.target.value)} placeholder={t('search')} className="max-w-xs" />
                    {type === 'file' && (
                        <Segmented
                            value={filter}
                            onChange={setFilter}
                            options={[
                                { value: '', label: t('all') },
                                { value: 'image', label: t('images') },
                                { value: 'file', label: t('documents') },
                            ]}
                        />
                    )}
                </div>
                {items.length === 0 && !loading ? (
                    <p className="py-8 text-center text-sm text-ink-faint">{t('media_empty')}</p>
                ) : (
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        {items.map((item) => (
                            <button
                                key={item.id}
                                type="button"
                                onClick={() => {
                                    onPick(item);
                                    onClose();
                                }}
                                className="group flex min-w-0 flex-col overflow-hidden rounded-control border border-line text-start transition-colors hover:border-accent"
                            >
                                <MediaThumb item={item} className="aspect-[4/3] w-full" />
                                <span className="truncate px-2 py-1.5 text-xs text-ink-soft group-hover:text-ink">{item.name}</span>
                            </button>
                        ))}
                    </div>
                )}
                {next && (
                    <Button size="sm" onClick={() => load(next)} loading={loading} className="self-center">
                        {t('load_more')}
                    </Button>
                )}
            </div>
        </Modal>
    );
}

export function MediaThumb({ item, className }: { item: MediaItem; className?: string }) {
    return item.is_image ? (
        <img src={item.url} alt="" loading="lazy" className={cn('bg-sunken object-cover', className)} />
    ) : (
        <span className={cn('grid place-items-center bg-sunken text-ink-faint', className)}>
            <span className="flex flex-col items-center gap-1">
                <Icon name="file" size={26} />
                <span className="text-[11px] font-semibold uppercase">{item.name.split('.').pop()}</span>
            </span>
        </span>
    );
}

/**
 * Drop zone / file browser uploading to the media library (JSON response).
 */
export function UploadZone({ accept, onUploaded }: { accept: string; onUploaded: (item: MediaItem) => void }) {
    const t = useT();
    const { urls } = useMediaConfig();
    const input = useRef<HTMLInputElement>(null);
    const [state, setState] = useState<'idle' | 'over' | 'uploading'>('idle');
    const [error, setError] = useState<string | null>(null);

    const upload = async (files: FileList | null) => {
        if (!files?.length) {
            return;
        }

        setState('uploading');
        setError(null);

        for (const file of Array.from(files)) {
            const body = new FormData();
            body.append('file', file);
            const response = await request(urls.store, { method: 'POST', body });

            if (response.ok) {
                onUploaded((await response.json()) as MediaItem);
            } else {
                const payload = (await response.json().catch(() => null)) as { errors?: Record<string, string[]> } | null;
                setError(payload?.errors?.file?.[0] ?? t('upload_failed'));
            }
        }

        setState('idle');
        if (input.current) {
            input.current.value = '';
        }
    };

    const onDrop = (event: DragEvent) => {
        event.preventDefault();
        upload(event.dataTransfer.files);
    };

    return (
        <div>
            <button
                type="button"
                onClick={() => input.current?.click()}
                onDragOver={(event) => {
                    event.preventDefault();
                    setState('over');
                }}
                onDragLeave={() => setState('idle')}
                onDrop={onDrop}
                className={cn(
                    'flex w-full flex-col items-center justify-center gap-1.5 rounded-control border-2 border-dashed px-4 py-6 text-sm transition-colors',
                    state === 'over' ? 'border-accent bg-accent-soft text-accent-strong' : 'border-line-strong text-ink-soft hover:border-accent hover:text-ink',
                )}
            >
                <Icon name={state === 'uploading' ? 'download' : 'plus'} size={22} className={state === 'uploading' ? 'animate-pulse' : ''} />
                {state === 'uploading' ? t('uploading') : t('drop_files')}
            </button>
            <input ref={input} type="file" accept={accept} multiple className="sr-only" onChange={(event) => upload(event.target.files)} />
            {error && (
                <p className="mt-2 text-[13px] text-danger" role="alert">
                    {error}
                </p>
            )}
        </div>
    );
}

export { formatSize };
