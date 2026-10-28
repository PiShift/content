import { router } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { PageSettings, type PageMeta } from '../../components/editor/PageSettings';
import { SectionEditor } from '../../components/editor/SectionEditor';
import { SectionLibrary } from '../../components/editor/SectionLibrary';
import { SectionList } from '../../components/editor/SectionList';
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
    urls: { index: string; update: string; destroy: string; preview: string; view: string | null };
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
                    <Button icon="eye" onClick={openPreview}>
                        <span className="hidden md:inline">{t('preview')}</span>
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

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(15rem,18rem)_minmax(0,1fr)] xl:grid-cols-[minmax(17rem,22rem)_minmax(0,1fr)]">
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

                <section className="min-w-0 rounded-panel border border-line bg-panel p-5 shadow-panel sm:p-6">
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
            </div>

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
    );
}
