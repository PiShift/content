import { localeDir, useT } from '../../lib/utils';
import { otherLocaleError, TranslationHint } from '../fields/FieldRenderer';
import { Button } from '../ui/Button';
import { FieldWrapper, SelectInput, TextArea, TextInput } from '../ui/Form';
import { Icon } from '../ui/Icon';

export interface PageMeta {
    title: Record<string, string>;
    slug: string;
    template: string;
    seo: { title?: Record<string, string>; description?: Record<string, string> };
}

export function PageSettings({
    meta,
    onChange,
    locale,
    errors,
    templates,
    isProtected,
    onDelete,
}: {
    meta: PageMeta;
    onChange: (meta: PageMeta) => void;
    locale: string;
    errors: Record<string, string>;
    templates: Array<{ value: string; label: string }>;
    isProtected: boolean;
    onDelete: () => void;
}) {
    const t = useT();
    const dir = localeDir(locale);

    return (
        <div className="flex flex-col gap-5">
            <div className="flex items-start gap-3">
                <span className="grid size-10 shrink-0 place-items-center rounded-control bg-sunken text-ink-soft">
                    <Icon name="settings" />
                </span>
                <h2 className="pt-2 text-base font-semibold">{t('settings')}</h2>
            </div>

            <FieldWrapper
                id="page-title"
                label={t('title')}
                required
                aside={<TranslationHint value={meta.title} locale={locale} />}
                error={errors[`title.${locale}`] ?? otherLocaleError(errors, 'title', locale)}
            >
                <TextInput
                    id="page-title"
                    dir={dir}
                    value={meta.title[locale] ?? ''}
                    invalid={Boolean(errors[`title.${locale}`])}
                    onChange={(event) => onChange({ ...meta, title: { ...meta.title, [locale]: event.target.value } })}
                />
            </FieldWrapper>

            <FieldWrapper id="page-slug" label={t('slug')} help={isProtected ? t('protected_page') : t('slug_help')} error={errors.slug}>
                <div className="flex items-center rounded-control border border-line-strong bg-sunken ps-3" dir="ltr">
                    <span className="text-sm text-ink-faint">/</span>
                    <TextInput
                        id="page-slug"
                        dir="ltr"
                        disabled={isProtected}
                        className="border-0 bg-transparent ps-1 focus:ring-0 disabled:text-ink-faint"
                        value={meta.slug}
                        onChange={(event) => onChange({ ...meta, slug: event.target.value.toLowerCase().replace(/[^a-z0-9\-/]/g, '-') })}
                    />
                </div>
            </FieldWrapper>

            {templates.length > 1 && (
                <FieldWrapper id="page-template" label={t('template')} error={errors.template}>
                    <SelectInput id="page-template" options={templates} value={meta.template} onChange={(event) => onChange({ ...meta, template: event.target.value })} />
                </FieldWrapper>
            )}

            <fieldset className="flex flex-col gap-4 rounded-control border border-line p-4">
                <legend className="px-1 text-[13px] font-semibold">{t('seo')}</legend>
                <FieldWrapper id="seo-title" label={t('seo_title')} aside={<TranslationHint value={meta.seo.title} locale={locale} />} error={errors[`seo.title.${locale}`]}>
                    <TextInput
                        id="seo-title"
                        dir={dir}
                        value={meta.seo.title?.[locale] ?? ''}
                        onChange={(event) => onChange({ ...meta, seo: { ...meta.seo, title: { ...(meta.seo.title ?? {}), [locale]: event.target.value } } })}
                    />
                </FieldWrapper>
                <FieldWrapper
                    id="seo-description"
                    label={t('seo_description')}
                    aside={<TranslationHint value={meta.seo.description} locale={locale} />}
                    error={errors[`seo.description.${locale}`]}
                >
                    <TextArea
                        id="seo-description"
                        dir={dir}
                        rows={3}
                        maxLength={500}
                        value={meta.seo.description?.[locale] ?? ''}
                        onChange={(event) =>
                            onChange({ ...meta, seo: { ...meta.seo, description: { ...(meta.seo.description ?? {}), [locale]: event.target.value } } })
                        }
                    />
                </FieldWrapper>
            </fieldset>

            {!isProtected && (
                <div className="border-t border-line pt-5">
                    <Button variant="danger" icon="trash" onClick={onDelete}>
                        {t('delete_page')}
                    </Button>
                </div>
            )}
        </div>
    );
}
