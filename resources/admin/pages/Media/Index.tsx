import { router } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import { MediaContext, formatSize, type MediaItem, type MediaUrls } from '../../components/media/media';
import { MediaThumb, UploadZone } from '../../components/media/MediaPicker';
import { Button } from '../../components/ui/Button';
import { FieldWrapper, Segmented, TextInput } from '../../components/ui/Form';
import { ConfirmModal, Modal } from '../../components/ui/Modal';
import { AdminLayout } from '../../layouts/AdminLayout';
import { LOCALE_NAMES, localeDir, useShared, useT } from '../../lib/utils';

interface Paginated {
    data: MediaItem[];
    current_page: number;
    last_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
    total: number;
}

export default function MediaIndex({ media, filters, urls, accept }: { media: Paginated; filters: { q: string; type: string }; urls: MediaUrls; accept: string }) {
    const t = useT();
    const [query, setQuery] = useState(filters.q);
    const [editing, setEditing] = useState<MediaItem | null>(null);

    const visit = (params: Record<string, string>) => router.get(urls.index, { ...filters, ...params }, { preserveState: true, preserveScroll: true, replace: true });

    useEffect(() => {
        if (query === filters.q) {
            return;
        }

        const timer = window.setTimeout(() => visit({ q: query }), 300);

        return () => window.clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [query]);

    return (
        <MediaContext.Provider value={{ urls, accept }}>
            <AdminLayout title={t('media')}>
                <div className="flex flex-col gap-5">
                    <UploadZone accept={accept} onUploaded={() => router.reload({ only: ['media'] })} />

                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <TextInput type="search" value={query} onChange={(event) => setQuery(event.target.value)} placeholder={t('search')} className="max-w-xs" />
                        <Segmented
                            value={filters.type}
                            onChange={(type) => visit({ type })}
                            options={[
                                { value: '', label: t('all') },
                                { value: 'image', label: t('images') },
                                { value: 'file', label: t('documents') },
                            ]}
                        />
                    </div>

                    {media.data.length === 0 ? (
                        <p className="rounded-panel border border-dashed border-line-strong bg-panel py-14 text-center text-sm text-ink-faint">{t('media_empty')}</p>
                    ) : (
                        <ul className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                            {media.data.map((item) => (
                                <li key={item.id}>
                                    <button
                                        type="button"
                                        onClick={() => setEditing(item)}
                                        className="group flex w-full min-w-0 flex-col overflow-hidden rounded-control border border-line bg-panel text-start transition-colors hover:border-accent"
                                    >
                                        <MediaThumb item={item} className="aspect-square w-full" />
                                        <span className="truncate px-2 pt-1.5 text-xs font-medium">{item.name}</span>
                                        <span className="px-2 pb-1.5 text-[11px] text-ink-faint" dir="ltr">
                                            {formatSize(item.size)}
                                        </span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}

                    {(media.prev_page_url || media.next_page_url) && (
                        <div className="flex items-center justify-center gap-3">
                            <Button size="sm" icon="chevron-left" disabled={!media.prev_page_url} onClick={() => media.prev_page_url && router.get(media.prev_page_url)} className="rtl:[&>svg]:rotate-180" />
                            <span className="text-sm text-ink-soft" dir="ltr">
                                {media.current_page} / {media.last_page}
                            </span>
                            <Button size="sm" icon="chevron-right" disabled={!media.next_page_url} onClick={() => media.next_page_url && router.get(media.next_page_url)} className="rtl:[&>svg]:rotate-180" />
                        </div>
                    )}
                </div>

                {editing && <MediaDetails item={editing} urls={urls} onClose={() => setEditing(null)} />}
            </AdminLayout>
        </MediaContext.Provider>
    );
}

function MediaDetails({ item, urls, onClose }: { item: MediaItem; urls: MediaUrls; onClose: () => void }) {
    const t = useT();
    const { admin } = useShared();
    const [name, setName] = useState(item.name);
    const [alt, setAlt] = useState<Record<string, string>>({ ...item.alt });
    const [confirming, setConfirming] = useState(false);
    const [copied, setCopied] = useState(false);

    const save = (event: FormEvent) => {
        event.preventDefault();
        router.put(urls.update.replace('__ID__', String(item.id)), { name, alt }, { preserveScroll: true, onSuccess: onClose });
    };

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(item.url);
            setCopied(true);
        } catch {
            window.prompt(t('copy_url'), item.url);
        }
    };

    return (
        <Modal
            open
            onClose={onClose}
            title={item.name}
            size="lg"
            footer={
                <>
                    <Button variant="danger" icon="trash" onClick={() => setConfirming(true)} className="me-auto">
                        {t('delete')}
                    </Button>
                    <Button onClick={onClose}>{t('cancel')}</Button>
                    <Button variant="primary" type="submit" form="media-details">
                        {t('save')}
                    </Button>
                </>
            }
        >
            <div className="grid gap-5 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
                <div className="flex flex-col gap-2">
                    <MediaThumb item={item} className="aspect-square w-full rounded-control object-contain" />
                    <p className="text-xs text-ink-faint" dir="ltr">
                        {[item.mime, item.width && item.height ? `${item.width}×${item.height}` : null, formatSize(item.size)].filter(Boolean).join(' · ')}
                    </p>
                    <Button size="sm" icon={copied ? 'check' : 'link'} onClick={copy} className="self-start">
                        {copied ? t('copied') : t('copy_url')}
                    </Button>
                </div>
                <form id="media-details" onSubmit={save} className="flex flex-col gap-4">
                    <FieldWrapper id="media-name" label={t('file_name')}>
                        <TextInput id="media-name" value={name} onChange={(event) => setName(event.target.value)} />
                    </FieldWrapper>
                    {item.is_image &&
                        admin.contentLocales.map((locale) => (
                            <FieldWrapper key={locale} id={`alt-${locale}`} label={`${t('alt_text')} · ${LOCALE_NAMES[locale] ?? locale}`} help={locale === admin.contentLocales[0] ? t('alt_help') : undefined}>
                                <TextInput id={`alt-${locale}`} dir={localeDir(locale)} value={alt[locale] ?? ''} onChange={(event) => setAlt({ ...alt, [locale]: event.target.value })} />
                            </FieldWrapper>
                        ))}
                </form>
            </div>
            <ConfirmModal
                open={confirming}
                onClose={() => setConfirming(false)}
                onConfirm={() => router.delete(urls.destroy.replace('__ID__', String(item.id)), { preserveScroll: true, onSuccess: onClose })}
                title={t('delete')}
                message={t('confirm_delete_media')}
                confirmLabel={t('delete')}
            />
        </Modal>
    );
}
