import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { FieldRenderer } from '../../components/fields/FieldRenderer';
import { MediaContext, type MediaUrls } from '../../components/media/media';
import { Badge } from '../../components/ui/Badge';
import { Button } from '../../components/ui/Button';
import { Segmented } from '../../components/ui/Form';
import { Icon } from '../../components/ui/Icon';
import { AdminLayout } from '../../layouts/AdminLayout';
import type { FieldSchema } from '../../lib/types';
import { useShared, useT } from '../../lib/utils';

interface Props {
    global: { key: string; label: string; icon: string; status: 'published' | 'draft'; hasChanges: boolean };
    fields: FieldSchema[];
    data: Record<string, unknown>;
    can: { publish: boolean };
    urls: { index: string; update: string; media: MediaUrls; mediaAccept: string };
}

export default function GlobalEdit({ global, fields, data: initialData, can, urls }: Props) {
    const t = useT();
    const { admin, errors } = useShared();
    const [data, setData] = useState<Record<string, unknown>>(initialData);
    const [locale, setLocale] = useState(admin.contentLocales.includes(admin.locale) ? admin.locale : admin.contentLocales[0]);
    const [dirty, setDirty] = useState(false);
    const [saving, setSaving] = useState<false | 'draft' | 'publish'>(false);
    const hasTranslatable = fields.some((field) => field.translatable || field.type === 'link' || (field.fields ?? []).some((sub) => sub.translatable || sub.type === 'link'));

    const save = (publish: boolean) => {
        setSaving(publish ? 'publish' : 'draft');
        router.put(urls.update, { data, publish } as never, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => setDirty(false),
            onFinish: () => setSaving(false),
        });
    };

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
                title={global.label}
                actions={
                    <>
                        <span className="me-1 hidden sm:inline-flex">
                            {dirty ? (
                                <Badge tone="warning" dot>
                                    {t('unsaved_changes')}
                                </Badge>
                            ) : global.hasChanges ? (
                                <Badge tone="warning">{t('unpublished_changes')}</Badge>
                            ) : (
                                <Badge tone={global.status === 'published' ? 'success' : 'neutral'}>{t(global.status)}</Badge>
                            )}
                        </span>
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
                        {t('globals')}
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
                    {fields.map((field) => (
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
                </section>
            </AdminLayout>
        </MediaContext.Provider>
    );
}
