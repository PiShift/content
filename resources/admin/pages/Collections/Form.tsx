import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { FieldRenderer } from '../../components/fields/FieldRenderer';
import { MediaContext, type MediaUrls } from '../../components/media/media';
import { Badge } from '../../components/ui/Badge';
import { Button } from '../../components/ui/Button';
import { Segmented } from '../../components/ui/Form';
import { Icon } from '../../components/ui/Icon';
import { ConfirmModal } from '../../components/ui/Modal';
import { AdminLayout } from '../../layouts/AdminLayout';
import type { CollectionSchema } from '../../lib/types';
import { pickText, useShared, useT } from '../../lib/utils';

interface Props {
    collection: CollectionSchema;
    record: { id: string | number | null; data: Record<string, unknown> };
    urls: { index: string; save: string; destroy: string | null; media: MediaUrls; mediaAccept: string };
}

export default function CollectionForm({ collection, record, urls }: Props) {
    const t = useT();
    const { admin, errors } = useShared();
    const [data, setData] = useState<Record<string, unknown>>(record.data);
    const [locale, setLocale] = useState(admin.contentLocales.includes(admin.locale) ? admin.locale : admin.contentLocales[0]);
    const [dirty, setDirty] = useState(false);
    const [saving, setSaving] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const isNew = record.id === null;
    const titleField = collection.titleField;
    const title = (titleField && pickText(data[titleField], admin.locale)) || (isNew ? `${t('new_record')} · ${collection.singularLabel}` : collection.singularLabel);
    const hasTranslatable = collection.fields.some((field) => field.translatable || field.type === 'link' || (field.fields ?? []).some((sub) => sub.translatable));

    const save = () => {
        setSaving(true);
        const options = {
            preserveScroll: true,
            onSuccess: () => setDirty(false),
            onFinish: () => setSaving(false),
        };

        if (isNew) {
            router.post(urls.save, { data } as never, options);
        } else {
            router.put(urls.save, { data } as never, { ...options, preserveState: true });
        }
    };

    useEffect(() => {
        const onKey = (event: KeyboardEvent) => {
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 's') {
                event.preventDefault();
                save();
            }
        };
        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    });

    useEffect(() => {
        if (!dirty) {
            return;
        }

        const onBeforeUnload = (event: BeforeUnloadEvent) => event.preventDefault();
        window.addEventListener('beforeunload', onBeforeUnload);
        const remove = router.on('before', (event) => {
            if (event.detail.visit.method === 'get' && !window.confirm(t('leave_unsaved'))) {
                event.preventDefault();
            }
        });

        return () => {
            window.removeEventListener('beforeunload', onBeforeUnload);
            remove();
        };
    }, [dirty, t]);

    return (
        <MediaContext.Provider value={{ urls: urls.media, accept: urls.mediaAccept }}>
            <AdminLayout
                title={title}
                actions={
                    <>
                        {dirty && (
                            <span className="me-1 hidden sm:inline-flex">
                                <Badge tone="warning" dot>
                                    {t('unsaved_changes')}
                                </Badge>
                            </span>
                        )}
                        <Button variant="primary" onClick={save} loading={saving}>
                            {t('save')}
                        </Button>
                    </>
                }
            >
                <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <a href={urls.index} className="inline-flex items-center gap-1.5 text-sm text-ink-soft hover:text-ink">
                        <Icon name="arrow-back" size={16} className="rtl:rotate-180" />
                        {collection.label}
                    </a>
                    {hasTranslatable && (
                        <Segmented label={t('language')} value={locale} onChange={setLocale} options={admin.contentLocales.map((code) => ({ value: code, label: code.toUpperCase() }))} />
                    )}
                </div>

                {Object.keys(errors).length > 0 && (
                    <p className="mb-4 flex items-center gap-2 rounded-control bg-danger-soft px-4 py-2.5 text-sm font-medium text-danger" role="alert">
                        <Icon name="alert" size={17} />
                        {t('errors_in_form')}
                    </p>
                )}

                <section className="mx-auto flex max-w-3xl flex-col gap-5 rounded-panel border border-line bg-panel p-5 shadow-panel sm:p-6">
                    {collection.fields.map((field) => (
                        <FieldRenderer
                            key={field.name}
                            field={field}
                            value={data[field.name]}
                            onChange={(value) => {
                                setData((current) => ({ ...current, [field.name]: value }));
                                setDirty(true);
                            }}
                            locale={locale}
                            path={`data.${field.name}`}
                            errors={errors}
                            siblings={data}
                        />
                    ))}

                    {urls.destroy && (
                        <div className="border-t border-line pt-5">
                            <Button variant="danger" icon="trash" onClick={() => setDeleting(true)}>
                                {t('delete')}
                            </Button>
                        </div>
                    )}
                </section>

                <ConfirmModal
                    open={deleting}
                    onClose={() => setDeleting(false)}
                    onConfirm={() => {
                        setDirty(false);
                        urls.destroy && router.delete(urls.destroy);
                    }}
                    title={t('delete')}
                    message={t('confirm_delete_record')}
                    confirmLabel={t('delete')}
                />
            </AdminLayout>
        </MediaContext.Provider>
    );
}
