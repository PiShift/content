import { router } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { LivePreview } from '../../components/editor/LivePreview';
import { PageSettings, type PageMeta } from '../../components/editor/PageSettings';
import { RevisionsModal } from '../../components/editor/RevisionsModal';
import { SectionEditor } from '../../components/editor/SectionEditor';
import { SectionLibrary } from '../../components/editor/SectionLibrary';
import { SectionList } from '../../components/editor/SectionList';
import { MediaContext, type MediaUrls } from '../../components/media/media';
import { Badge } from '../../components/ui/Badge';
import { Button } from '../../components/ui/Button';
import { Segmented } from '../../components/ui/Form';
import { Icon } from '../../components/ui/Icon';
import { ConfirmModal } from '../../components/ui/Modal';
import { AdminLayout } from '../../layouts/AdminLayout';
import type { PageDocument, SectionData, SectionTypeSchema } from '../../lib/types';
import { cloneWithNewIds, cn, emptyValue, hasErrorsUnder, pickText, uid, useShared, useT } from '../../lib/utils';

interface Props {
    page: {
        id: number;
        key: string;
        title: Record<string, string>;
        slug: string;
        template: string;
        seo: PageMeta['seo'];
        status: 'published' | 'draft';
        hasChanges: boolean;
        protected: boolean;
    };
    document: PageDocument;
    sectionTypes: SectionTypeSchema[];
    templates: Array<{ value: string; label: string }>;
    can: { publish: boolean };
    urls: {
        index: string;
        update: string;
        destroy: string;
        preview: string;
        livePreview: string;
        revisions: string;
        restore: string;
        view: string | null;
        media: MediaUrls;
        mediaAccept: string;
    };
}

const SETTINGS = '__settings';

export default function PageEdit({ page, document: initialDocument, sectionTypes, templates, can, urls }: Props) {
    const t = useT();
    const { admin, errors } = useShared();
    const types = useMemo(() => Object.fromEntries(sectionTypes.map((type) => [type.key, type])), [sectionTypes]);

    const [sections, setSections] = useState<SectionData[]>(initialDocument.sections);
    const [meta, setMeta] = useState<PageMeta>({ title: { ...page.title }, slug: page.slug, template: page.template, seo: { ...page.seo } });
    const [selectedId, setSelectedId] = useState<string>(initialDocument.sections[0]?.id ?? SETTINGS);
    const [locale, setLocale] = useState(admin.contentLocales.includes(admin.locale) ? admin.locale : admin.contentLocales[0]);
    const [dirty, setDirty] = useState(false);
    const [saving, setSaving] = useState<false | 'draft' | 'publish'>(false);
    const [libraryOpen, setLibraryOpen] = useState(false);
    const [removingId, setRemovingId] = useState<string | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [historyOpen, setHistoryOpen] = useState(false);
    const [previewOpen, setPreviewOpen] = useState<boolean>(() => readPreference('content.preview', true));
    const liveDocument = useMemo(() => ({ ...initialDocument, sections }), [initialDocument, sections]);

    const togglePreview = () => {
        setPreviewOpen((open) => {
            writePreference('content.preview', !open);

            return !open;
        });
    };

    // A click in the live preview selects the section, then scrolls to the field if one was marked.
    const selectFromPreview = useCallback(
        (sectionId: string, field: string | null) => {
            const index = sections.findIndex((section) => section.id === sectionId);
            if (index === -1) {
                return;
            }

            setSelectedId(sectionId);

            window.setTimeout(() => {
                const target =
                    (field && window.document.querySelector<HTMLElement>(`[data-field-path="sections.${index}.data.${field}"]`)) ||
                    window.document.querySelector<HTMLElement>('[data-section-editor]');
                target?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                target?.querySelector<HTMLElement>('input, textarea, select, [contenteditable="true"]')?.focus({ preventScroll: true });
            }, 60);
        },
        [sections],
    );

    const changeSections = (next: SectionData[]) => {
        setSections(next);
        setDirty(true);
    };

    const save = useCallback(
        (publish: boolean, onSaved?: () => void) => {
            setSaving(publish ? 'publish' : 'draft');

            router.put(
                urls.update,
                { ...meta, document: { ...initialDocument, sections }, publish } as never,
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        setDirty(false);
                        onSaved?.();
                    },
                    onFinish: () => setSaving(false),
                },
            );
        },
        [urls.update, meta, sections, initialDocument],
    );

    // Ctrl/Cmd+S saves the draft.
    useEffect(() => {
        const onKey = (event: KeyboardEvent) => {
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 's') {
                event.preventDefault();
                save(false);
            }
        };
        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [save]);

    // Warn before leaving with unsaved changes (browser navigation and Inertia links).
    useEffect(() => {
        if (!dirty) {
            return;
        }

        const onBeforeUnload = (event: BeforeUnloadEvent) => event.preventDefault();
        window.addEventListener('beforeunload', onBeforeUnload);
        const removeRouterGuard = router.on('before', (event) => {
            if (event.detail.visit.method === 'get' && !window.confirm(t('leave_unsaved'))) {
                event.preventDefault();
            }
        });

        return () => {
            window.removeEventListener('beforeunload', onBeforeUnload);
            removeRouterGuard();
        };
    }, [dirty, t]);

    const addSection = (type: SectionTypeSchema) => {
        const section: SectionData = {
            id: uid(),
            type: type.key,
            version: type.version,
            variant: Object.keys(type.variants)[0] ?? null,
            style: Object.fromEntries(Object.entries(type.styles).map(([option, values]) => [option, Object.keys(values)[0]])),
            hidden: false,
            data: Object.fromEntries(type.fields.map((field) => [field.name, emptyValue(field)])),
        };
        const at = sections.findIndex((item) => item.id === selectedId);
        changeSections(at === -1 ? [...sections, section] : [...sections.slice(0, at + 1), section, ...sections.slice(at + 1)]);
        setSelectedId(section.id);
    };

    const updateSection = (id: string, patch: (section: SectionData) => SectionData) => changeSections(sections.map((section) => (section.id === id ? patch(section) : section)));

    const duplicateSection = (id: string) => {
        const index = sections.findIndex((section) => section.id === id);
        const copy = { ...cloneWithNewIds(sections[index]), id: uid() };
        changeSections([...sections.slice(0, index + 1), copy, ...sections.slice(index + 1)]);
        setSelectedId(copy.id);
    };

    const removeSection = (id: string) => {
        const index = sections.findIndex((section) => section.id === id);
        const next = sections.filter((section) => section.id !== id);
        changeSections(next);
        if (selectedId === id) {
            setSelectedId(next[Math.max(0, index - 1)]?.id ?? SETTINGS);
        }
    };

    const openPreview = () => {
        if (!dirty) {
            window.open(urls.preview, '_blank', 'noopener');

            return;
        }

        const preview = window.open('', '_blank');
        save(false, () => {
            if (preview) {
                preview.location.href = urls.preview;
            }
        });
    };

    const selectedIndex = sections.findIndex((section) => section.id === selectedId);
    const selected = sections[selectedIndex];
    const metaInvalid = ['title', 'slug', 'template', 'seo'].some((key) => hasErrorsUnder(errors, key));
    const hasErrors = Object.keys(errors).length > 0;

    return (
        <MediaContext.Provider value={{ urls: urls.media, accept: urls.mediaAccept }}>
        <AdminLayout
            wide
            title={pickText(meta.title, admin.locale) || page.key}
            actions={
                <>
                    <span className="me-1 hidden items-center gap-2 sm:flex">
                        {dirty ? (
                            <Badge tone="warning" dot>
                                {t('unsaved_changes')}
                            </Badge>
                        ) : page.hasChanges && page.status === 'published' ? (
                            <Badge tone="warning">{t('unpublished_changes')}</Badge>
                        ) : (
                            <Badge tone={page.status === 'published' ? 'success' : 'neutral'}>{t(page.status)}</Badge>
                        )}
                    </span>
                    <Button icon="undo" onClick={() => setHistoryOpen(true)} aria-label={t('history')}>
                        <span className="hidden xl:inline">{t('history')}</span>
                    </Button>
                    <Button icon={previewOpen ? 'eye-off' : 'eye'} onClick={togglePreview} aria-label={previewOpen ? t('hide_preview') : t('show_preview')}>
                        <span className="hidden md:inline">{previewOpen ? t('hide_preview') : t('show_preview')}</span>
                    </Button>
                    <Button onClick={() => save(false)} loading={saving === 'draft'} disabled={saving !== false}>
                        {t('save_draft')}
                    </Button>
                    {can.publish && (
                        <Button variant="primary" onClick={() => save(true)} loading={saving === 'publish'} disabled={saving !== false}>
                            {t('publish')}
                        </Button>
                    )}
                </>
            }
        >
            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <a href={urls.index} className="inline-flex items-center gap-1.5 text-sm text-ink-soft hover:text-ink">
                    <Icon name="arrow-back" size={16} className="rtl:rotate-180" />
                    {t('pages')}
                </a>
                <div className="flex flex-wrap items-center gap-3">
                    <button type="button" onClick={openPreview} className="inline-flex items-center gap-1.5 text-sm text-ink-soft hover:text-ink">
                        <Icon name="external" size={16} />
                        {t('preview')}
                    </button>
                    {urls.view && page.status === 'published' && (
                        <a href={urls.view} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1.5 text-sm text-ink-soft hover:text-ink">
                            <Icon name="external" size={16} />
                            {t('view_page')}
                        </a>
                    )}
                    <Segmented
                        label={t('language')}
                        value={locale}
                        onChange={setLocale}
                        options={admin.contentLocales.map((code) => ({ value: code, label: code.toUpperCase() }))}
                    />
                </div>
            </div>

            {hasErrors && (
                <p className="mb-4 flex items-center gap-2 rounded-control bg-danger-soft px-4 py-2.5 text-sm font-medium text-danger" role="alert">
                    <Icon name="alert" size={17} />
                    {t('errors_in_form')}
                </p>
            )}

            <div
                className={cn(
                    'grid items-start gap-5 lg:grid-cols-[minmax(15rem,18rem)_minmax(0,1fr)]',
                    previewOpen ? '2xl:grid-cols-[17rem_minmax(22rem,1fr)_minmax(0,1.2fr)]' : 'xl:grid-cols-[minmax(17rem,22rem)_minmax(0,1fr)]',
                )}
            >
                <aside className="flex flex-col gap-3 lg:sticky lg:top-20">
                    <button
                        type="button"
                        onClick={() => setSelectedId(SETTINGS)}
                        className={cn(
                            'flex items-center gap-2.5 rounded-control border bg-panel px-3 py-2.5 text-start text-sm font-medium transition-colors',
                            selectedId === SETTINGS ? 'border-accent ring-2 ring-accent/15' : metaInvalid ? 'border-danger' : 'border-line hover:border-line-strong',
                        )}
                    >
                        <Icon name="settings" size={17} className="text-ink-soft" />
                        <span className="flex-1">{t('settings')}</span>
                        {metaInvalid && <Icon name="alert" size={15} className="text-danger" />}
                    </button>

                    <div>
                        <p className="mb-2 px-1 text-[11px] font-semibold tracking-wider text-ink-faint uppercase">{t('sections')}</p>
                        <SectionList
                            sections={sections}
                            types={types}
                            selectedId={selectedId}
                            locale={locale}
                            errors={errors}
                            onSelect={setSelectedId}
                            onReorder={changeSections}
                            onToggleHidden={(id) => updateSection(id, (section) => ({ ...section, hidden: !section.hidden }))}
                            onDuplicate={duplicateSection}
                            onRemove={setRemovingId}
                        />
                    </div>

                    <Button icon="plus" onClick={() => setLibraryOpen(true)} className="w-full border-dashed">
                        {t('add_section')}
                    </Button>
                </aside>

                <section data-section-editor className="min-w-0 scroll-mt-24 rounded-panel border border-line bg-panel p-5 shadow-panel sm:p-6">
                    {selectedId === SETTINGS ? (
                        <PageSettings
                            meta={meta}
                            onChange={(next) => {
                                setMeta(next);
                                setDirty(true);
                            }}
                            locale={locale}
                            errors={errors}
                            templates={templates}
                            isProtected={page.protected}
                            onDelete={() => setDeleting(true)}
                        />
                    ) : selected ? (
                        <SectionEditor
                            key={selected.id}
                            section={selected}
                            index={selectedIndex}
                            type={types[selected.type]}
                            locale={locale}
                            errors={errors}
                            onChange={(next) => updateSection(selected.id, () => next)}
                            onRemove={() => setRemovingId(selected.id)}
                        />
                    ) : (
                        <p className="py-10 text-center text-sm text-ink-faint">{t('select_section')}</p>
                    )}
                </section>

                {previewOpen && (
                    <div className="lg:col-span-2 2xl:sticky 2xl:top-20 2xl:col-span-1 2xl:h-[calc(100dvh-6rem)]">
                        <LivePreview url={urls.livePreview} document={liveDocument} locale={locale} onSelect={selectFromPreview} />
                    </div>
                )}
            </div>

            <RevisionsModal
                open={historyOpen}
                onClose={() => setHistoryOpen(false)}
                listUrl={urls.revisions}
                restoreUrl={urls.restore}
                onBeforeRestore={() => setDirty(false)}
            />

            <SectionLibrary open={libraryOpen} onClose={() => setLibraryOpen(false)} types={sectionTypes} onPick={addSection} />

            <ConfirmModal
                open={removingId !== null}
                onClose={() => setRemovingId(null)}
                onConfirm={() => removingId && removeSection(removingId)}
                title={t('remove')}
                message={t('confirm_remove_section')}
                confirmLabel={t('remove')}
            />

            <ConfirmModal
                open={deleting}
                onClose={() => setDeleting(false)}
                onConfirm={() => {
                    setDirty(false);
                    router.delete(urls.destroy);
                }}
                title={t('delete_page')}
                message={t('confirm_delete_page')}
                confirmLabel={t('delete')}
            />
        </AdminLayout>
        </MediaContext.Provider>
    );
}

function readPreference(key: string, fallback: boolean): boolean {
    try {
        const stored = window.localStorage.getItem(key);

        return stored === null ? fallback : stored === '1';
    } catch {
        return fallback;
    }
}

function writePreference(key: string, value: boolean): void {
    try {
        window.localStorage.setItem(key, value ? '1' : '0');
    } catch {
        // Private mode or blocked storage: the preference just isn't remembered.
    }
}
