import { useId } from 'react';
import { localeDir, useT } from '../../lib/utils';
import { FieldWrapper, Switch, TextInput } from '../ui/Form';
import type { FieldProps } from './FieldRenderer';
import { TranslationHint } from './FieldRenderer';

interface LinkValue {
    url?: string | null;
    label?: Record<string, string>;
    new_tab?: boolean;
}

export function LinkField({ field, value, onChange, locale, path, errors }: FieldProps) {
    const t = useT();
    const id = useId();
    const link: LinkValue = value && typeof value === 'object' ? (value as LinkValue) : {};
    const set = (patch: Partial<LinkValue>) => onChange({ url: '', label: {}, new_tab: false, ...link, ...patch });

    return (
        <fieldset className="flex min-w-0 flex-col gap-3 rounded-control border border-line p-3">
            <legend className="px-1 text-[13px] font-semibold">
                {field.label}
                {field.required && <span className="ms-0.5 text-danger">*</span>}
            </legend>
            {field.withLabel !== false && (
                <FieldWrapper id={`${id}-label`} label={t('link_label')} aside={<TranslationHint value={link.label} locale={locale} />} error={errors[`${path}.label.${locale}`]}>
                    <TextInput
                        id={`${id}-label`}
                        dir={localeDir(locale)}
                        value={link.label?.[locale] ?? ''}
                        onChange={(event) => set({ label: { ...(link.label ?? {}), [locale]: event.target.value } })}
                    />
                </FieldWrapper>
            )}
            <FieldWrapper id={`${id}-url`} label={t('url')} help={field.help ?? '/about · https://…'} error={errors[`${path}.url`] ?? errors[path]}>
                <TextInput id={`${id}-url`} dir="ltr" value={link.url ?? ''} invalid={Boolean(errors[`${path}.url`])} onChange={(event) => set({ url: event.target.value })} />
            </FieldWrapper>
            <Switch checked={Boolean(link.new_tab)} onChange={(checked) => set({ new_tab: checked })} label={t('new_tab')} />
        </fieldset>
    );
}
