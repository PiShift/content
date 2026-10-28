import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { Badge } from '../../components/ui/Badge';
import { Button } from '../../components/ui/Button';
import { FieldWrapper, SelectInput, TextInput } from '../../components/ui/Form';
import { Icon } from '../../components/ui/Icon';
import { Modal } from '../../components/ui/Modal';
import { AdminLayout } from '../../layouts/AdminLayout';
import { LOCALE_NAMES, localeDir, pickText, useShared, useT } from '../../lib/utils';

interface PageRow {
    id: number;
    key: string;
    title: Record<string, string>;
    path: string | null;
    status: 'published' | 'draft';
    hasChanges: boolean;
    updatedAt: string | null;
    editUrl: string;
}

export default function PagesIndex({
    pages,
    templates,
    createUrl,
}: {
    pages: PageRow[];
    templates: Array<{ value: string; label: string }>;
    createUrl: string;
}) {
    const t = useT();
    const { admin } = useShared();
    const [creating, setCreating] = useState(false);
    const formatDate = (iso: string | null) =>
        iso ? new Intl.DateTimeFormat(admin.locale, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(iso)) : '—';

    return (
        <AdminLayout
            title={t('pages')}
            actions={
                <Button variant="primary" icon="plus" onClick={() => setCreating(true)}>
                    {t('new_page')}
                </Button>
            }
        >
            {pages.length === 0 ? (
                <div className="grid place-items-center rounded-panel border border-dashed border-line-strong bg-panel px-6 py-16 text-center">
                    <Icon name="pages" size={28} className="text-ink-faint" />
                    <p className="mt-3 text-sm text-ink-soft">{t('no_pages')}</p>
                    <Button className="mt-4" variant="primary" icon="plus" onClick={() => setCreating(true)}>
                        {t('new_page')}
                    </Button>
                </div>
            ) : (
                <ul className="divide-y divide-line overflow-hidden rounded-panel border border-line bg-panel shadow-panel">
                    {pages.map((page) => (
                        <li key={page.id}>
                            <Link href={page.editUrl} className="group flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3.5 transition-colors hover:bg-canvas sm:px-5">
                                <div className="min-w-0 flex-1">
                                    <p className="truncate font-semibold group-hover:text-accent-strong">{pickText(page.title, admin.locale) || page.key}</p>
                                    <p className="truncate text-[13px] text-ink-faint" dir="ltr">
                                        {page.path ?? '—'}
                                    </p>
                                </div>
                                <div className="flex items-center gap-2">
                                    {page.hasChanges && page.status === 'published' && (
                                        <Badge tone="warning" dot>
                                            {t('unpublished_changes')}
                                        </Badge>
                                    )}
                                    <Badge tone={page.status === 'published' ? 'success' : 'neutral'}>{t(page.status)}</Badge>
                                </div>
                                <p className="w-full text-[13px] text-ink-faint sm:w-40 sm:text-end">
                                    {t('updated')} · {formatDate(page.updatedAt)}
                                </p>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}

            <CreatePageModal open={creating} onClose={() => setCreating(false)} templates={templates} createUrl={createUrl} />
        </AdminLayout>
    );
}

function CreatePageModal({
    open,
    onClose,
    templates,
    createUrl,
}: {
    open: boolean;
    onClose: () => void;
    templates: Array<{ value: string; label: string }>;
    createUrl: string;
}) {
    const t = useT();
    const { admin } = useShared();
    const locales = admin.contentLocales;
    const form = useForm<{ title: Record<string, string>; slug: string; template: string }>({
        title: Object.fromEntries(locales.map((locale) => [locale, ''])),
        slug: '',
        template: templates[0]?.value ?? 'default',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(createUrl, { onSuccess: () => form.reset() });
    };

    return (
        <Modal
            open={open}
            onClose={onClose}
            title={t('new_page')}
            footer={
                <>
                    <Button onClick={onClose}>{t('cancel')}</Button>
                    <Button variant="primary" type="submit" form="create-page" loading={form.processing}>
                        {t('create')}
                    </Button>
                </>
            }
        >
            <form id="create-page" onSubmit={submit} className="flex flex-col gap-4">
                {locales.map((locale, index) => (
                    <FieldWrapper
                        key={locale}
                        id={`title-${locale}`}
                        label={`${t('title')} · ${LOCALE_NAMES[locale] ?? locale}`}
                        required={index === 0}
                        error={form.errors[`title.${locale}` as keyof typeof form.errors]}
                    >
                        <TextInput
                            id={`title-${locale}`}
                            dir={localeDir(locale)}
                            value={form.data.title[locale] ?? ''}
                            onChange={(event) => form.setData('title', { ...form.data.title, [locale]: event.target.value })}
                        />
                    </FieldWrapper>
                ))}
                <FieldWrapper id="slug" label={t('slug')} help={t('slug_help')} error={form.errors.slug} required>
                    <div className="flex items-center rounded-control border border-line-strong bg-sunken ps-3 focus-within:border-accent" dir="ltr">
                        <span className="text-sm text-ink-faint">/</span>
                        <TextInput
                            id="slug"
                            dir="ltr"
                            className="border-0 bg-transparent ps-1 focus:ring-0"
                            value={form.data.slug}
                            onChange={(event) => form.setData('slug', event.target.value.toLowerCase().replace(/[^a-z0-9\-/]/g, '-'))}
                        />
                    </div>
                </FieldWrapper>
                {templates.length > 1 && (
                    <FieldWrapper id="template" label={t('template')} error={form.errors.template}>
                        <SelectInput id="template" options={templates} value={form.data.template} onChange={(event) => form.setData('template', event.target.value)} />
                    </FieldWrapper>
                )}
            </form>
        </Modal>
    );
}
