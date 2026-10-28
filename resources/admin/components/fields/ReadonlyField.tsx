import type { FieldProps } from './FieldRenderer';
import { FieldWrapper } from '../ui/Form';
import { pickText, useShared, useT } from '../../lib/utils';

/**
 * A value shown but not editable (e.g. set by the system or a background job).
 */
export function ReadonlyField({ field, value }: FieldProps) {
    const t = useT();
    const { admin } = useShared();
    let text = field.translatable ? pickText(value, admin.locale) : value === null || value === undefined ? '' : String(value);

    if (field.options && text in field.options) {
        text = field.options[text];
    } else if (field.type === 'toggle') {
        text = value ? t('yes') : t('no');
    } else if ((field.type === 'date' || field.type === 'datetime') && text) {
        text = new Intl.DateTimeFormat(admin.locale, { dateStyle: 'medium', timeStyle: field.type === 'datetime' ? 'short' : undefined }).format(new Date(text));
    } else if (field.type === 'upload' && text) {
        text = text.split('/').pop() ?? text;
    }

    return (
        <FieldWrapper label={field.label} help={field.help}>
            <p className="min-h-10 rounded-control bg-sunken px-3 py-2 text-[15px] whitespace-pre-line text-ink" dir="auto">
                {text || <span className="text-ink-faint">—</span>}
            </p>
        </FieldWrapper>
    );
}
