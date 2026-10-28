import { useId, useRef } from 'react';
import type { FieldProps } from './FieldRenderer';
import { formatSize } from '../media/media';
import { Button } from '../ui/Button';
import { FieldWrapper } from '../ui/Form';
import { Icon } from '../ui/Icon';
import { useT } from '../../lib/utils';

/**
 * A file sent with the form (not through the media library): the value is the stored path,
 * or the File chosen in the browser until the form is saved.
 */
export function UploadField({ field, value, onChange, path, errors }: FieldProps) {
    const t = useT();
    const id = useId();
    const input = useRef<HTMLInputElement>(null);
    const chosen = value instanceof File ? value : null;
    const stored = typeof value === 'string' && value !== '' ? value.split('/').pop() : null;
    const help = [field.help, field.maxSize ? t('max_size', { size: formatSize(field.maxSize * 1024) }) : null].filter(Boolean).join(' · ');

    return (
        <FieldWrapper id={id} label={field.label} required={field.required} help={help} error={errors[path]}>
            <div className="flex flex-wrap items-center gap-3 rounded-control border border-dashed border-line-strong bg-canvas px-3 py-2.5">
                <Icon name="file" size={20} className="shrink-0 text-ink-soft" />
                <span className="min-w-0 flex-1 truncate text-sm" dir="auto">
                    {chosen ? (
                        <>
                            <span className="text-xs text-ink-faint">{t('new_file')}: </span>
                            {chosen.name} <span className="text-ink-faint">({formatSize(chosen.size)})</span>
                        </>
                    ) : stored ? (
                        <>
                            <span className="text-xs text-ink-faint">{t('current_file')}: </span>
                            {stored}
                        </>
                    ) : (
                        <span className="text-ink-faint">—</span>
                    )}
                </span>
                <Button size="sm" icon="upload" onClick={() => input.current?.click()}>
                    {stored || chosen ? t('replace_file') : t('choose_file')}
                </Button>
                <input
                    ref={input}
                    id={id}
                    type="file"
                    className="sr-only"
                    accept={field.accept?.join(',')}
                    onChange={(event) => {
                        const file = event.target.files?.[0];
                        if (file) {
                            onChange(file);
                        }
                        event.target.value = '';
                    }}
                />
            </div>
        </FieldWrapper>
    );
}
