import { useId } from 'react';
import type { FieldSchema } from '../../lib/types';
import { cn, localeDir, missingTranslation, useT } from '../../lib/utils';
import { FieldWrapper, SelectInput, Switch, TextArea, TextInput } from '../ui/Form';
import { LinkField } from './LinkField';
import { MediaField } from './MediaField';
import { RichTextField } from './RichTextField';
import { RepeaterField } from './RepeaterField';
import { SlugField } from './SlugField';
import { ReadonlyField } from './ReadonlyField';
import { UploadField } from './UploadField';

export interface FieldProps {
    field: FieldSchema;
    value: unknown;
    onChange: (value: unknown) => void;
    /** Locale being edited for translatable fields. */
    locale: string;
    /** Dotted path matching the server's validation keys, e.g. "sections.0.data.title". */
    path: string;
    errors: Record<string, string>;
    /** Values of the fields next to this one, for conditional fields (visibleWhen). */
    siblings?: Record<string, unknown>;
}

/**
 * Picks the input for a field schema sent by the server.
 */
export function FieldRenderer(props: FieldProps) {
    const condition = props.field.visibleWhen;

    if (condition && props.siblings && !condition.values.includes(String(props.siblings[condition.field] ?? ''))) {
        return null;
    }

    // data-field-path lets the live preview jump to a field.
    return (
        <div data-field-path={props.path} className="min-w-0 scroll-mt-24">
            <FieldInput {...props} />
        </div>
    );
}

function FieldInput(props: FieldProps) {
    if (props.field.readonly) {
        return <ReadonlyField {...props} />;
    }

    if (props.field.slugFrom) {
        return <SlugField {...props} />;
    }

    switch (props.field.type) {
        case 'upload':
            return <UploadField {...props} />;
        case 'color':
            return <ColorField {...props} />;
        case 'repeater':
            return <RepeaterField {...props} />;
        case 'link':
            return <LinkField {...props} />;
        case 'image':
        case 'file':
            return <MediaField {...props} />;
        case 'toggle':
            return <ToggleField {...props} />;
        case 'rich-text':
            return <RichTextField {...props} />;
        default:
            return <ScalarField {...props} />;
    }
}

function ToggleField({ field, value, onChange, path, errors }: FieldProps) {
    const id = useId();

    return (
        <FieldWrapper help={field.help} error={errors[path]}>
            <Switch id={id} checked={Boolean(value)} onChange={onChange} label={field.label} />
        </FieldWrapper>
    );
}

function ColorField({ field, value, onChange, path, errors }: FieldProps) {
    const id = useId();
    const current = typeof value === 'string' && /^#[0-9a-f]{6}$/i.test(value) ? value : '';
    const fallback = typeof field.default === 'string' ? field.default : '#000000';

    return (
        <FieldWrapper id={id} label={field.label} required={field.required} help={field.help} error={errors[path]}>
            <div className="flex items-center gap-2">
                <input
                    type="color"
                    aria-label={field.label}
                    value={current || fallback}
                    onChange={(event) => onChange(event.target.value)}
                    className="h-10 w-12 shrink-0 cursor-pointer rounded-control border border-line-strong bg-panel p-1"
                />
                <TextInput
                    id={id}
                    dir="ltr"
                    maxLength={7}
                    invalid={Boolean(errors[path])}
                    placeholder={fallback}
                    value={typeof value === 'string' ? value : ''}
                    onChange={(event) => onChange(event.target.value || null)}
                    className="max-w-36 font-mono text-sm"
                />
            </div>
        </FieldWrapper>
    );
}

/**
 * Text-like fields; translatable ones edit the current locale's entry of {ar, fr, …}.
 */
function ScalarField({ field, value, onChange, locale, path, errors }: FieldProps) {
    const t = useT();
    const id = useId();
    const translations = field.translatable && value && typeof value === 'object' ? (value as Record<string, unknown>) : {};
    const current = field.translatable ? translations[locale] : value;
    const errorKey = field.translatable ? `${path}.${locale}` : path;
    const error = errors[errorKey] ?? (field.translatable ? otherLocaleError(errors, path, locale) : errors[path]);
    const dir = field.translatable ? localeDir(locale) : ['number', 'date', 'datetime'].includes(field.type) ? 'ltr' : 'auto';

    const set = (next: unknown) => onChange(field.translatable ? { ...translations, [locale]: next } : next);

    const common = { id, dir, invalid: Boolean(error), placeholder: field.placeholder ?? undefined };

    let input;
    switch (field.type) {
        case 'textarea':
        case 'rich-text':
            input = (
                <TextArea
                    {...common}
                    rows={field.rows ?? (field.type === 'rich-text' ? 6 : 4)}
                    maxLength={field.maxLength}
                    value={(current as string) ?? ''}
                    onChange={(event) => set(event.target.value)}
                    className={cn(field.type === 'rich-text' && 'font-mono text-[13px]')}
                />
            );
            break;
        case 'number':
            input = (
                <TextInput
                    {...common}
                    type="number"
                    min={field.min ?? undefined}
                    max={field.max ?? undefined}
                    step={field.integer ? 1 : 'any'}
                    value={current === null || current === undefined ? '' : String(current)}
                    onChange={(event) => set(event.target.value === '' ? null : Number(event.target.value))}
                />
            );
            break;
        case 'date':
            input = <TextInput {...common} type="date" value={((current as string) ?? '').slice(0, 10)} onChange={(event) => set(event.target.value || null)} />;
            break;
        case 'datetime':
            input = <TextInput {...common} type="datetime-local" value={((current as string) ?? '').slice(0, 16)} onChange={(event) => set(event.target.value || null)} />;
            break;
        case 'select':
        case 'icon':
            input =
                field.options && Object.keys(field.options).length > 0 ? (
                    <SelectInput
                        id={id}
                        invalid={Boolean(error)}
                        placeholder={field.required ? undefined : t('none')}
                        options={Object.entries(field.options).map(([value, label]) => ({ value, label }))}
                        value={(current as string) ?? ''}
                        onChange={(event) => set(event.target.value || null)}
                    />
                ) : (
                    <TextInput {...common} dir="ltr" value={(current as string) ?? ''} onChange={(event) => set(event.target.value || null)} />
                );
            break;
        default:
            input = (
                <TextInput
                    {...common}
                    type={field.inputType ?? 'text'}
                    dir={field.inputType && field.inputType !== 'text' ? 'ltr' : common.dir}
                    autoComplete={field.inputType === 'password' ? 'new-password' : undefined}
                    maxLength={field.maxLength}
                    value={(current as string) ?? ''}
                    onChange={(event) => set(event.target.value)}
                />
            );
    }

    return (
        <FieldWrapper
            id={id}
            label={field.label}
            required={field.required}
            help={field.help}
            error={error}
            aside={field.translatable ? <TranslationHint value={value} locale={locale} /> : undefined}
        >
            {input}
        </FieldWrapper>
    );
}

/**
 * An error on another locale of a translatable value, labelled with that locale
 * (e.g. "[AR] The title field is required."), so it isn't hidden while editing French.
 */
export function otherLocaleError(errors: Record<string, string>, path: string, locale: string): string | undefined {
    const key = Object.keys(errors).find((key) => key.startsWith(`${path}.`) && key !== `${path}.${locale}` && !key.slice(path.length + 1).includes('.'));

    return key ? `[${key.slice(path.length + 1).toUpperCase()}] ${errors[key]}` : errors[path];
}

export function TranslationHint({ value, locale }: { value: unknown; locale: string }) {
    const t = useT();
    const missing = missingTranslation(value, locale);

    return (
        <span
            className={cn('inline-flex items-center gap-1 text-[11px] font-semibold uppercase', missing ? 'text-warning' : 'text-ink-faint')}
            title={missing ? t('translation_missing') : undefined}
        >
            {missing && <span className="size-1.5 rounded-full bg-current" aria-hidden="true" />}
            {locale}
            {missing && <span className="sr-only">{t('translation_missing')}</span>}
        </span>
    );
}
