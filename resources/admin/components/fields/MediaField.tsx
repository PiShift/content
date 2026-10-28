import { useId } from 'react';
import { useT } from '../../lib/utils';
import { FieldWrapper, TextInput } from '../ui/Form';
import { Icon } from '../ui/Icon';
import type { FieldProps } from './FieldRenderer';

/**
 * Image or file reference: a media library id, or a path/URL for imported content.
 */
export function MediaField({ field, value, onChange, path, errors }: FieldProps) {
    const t = useT();
    const id = useId();
    const text = value === null || value === undefined ? '' : String(value);
    const previewUrl = typeof value === 'string' && /\.(png|jpe?g|webp|gif|svg)$/i.test(value) ? toUrl(value) : null;

    return (
        <FieldWrapper id={id} label={field.label} required={field.required} help={field.help ?? t(field.type === 'image' ? 'image_path' : 'file_path')} error={errors[path]}>
            <div className="flex items-center gap-3">
                <div className="grid size-12 shrink-0 place-items-center overflow-hidden rounded-control border border-line bg-sunken text-ink-faint">
                    {previewUrl ? <img src={previewUrl} alt="" className="size-full object-cover" /> : <Icon name={field.type === 'image' ? 'image' : 'file'} />}
                </div>
                <TextInput id={id} dir="ltr" value={text} invalid={Boolean(errors[path])} onChange={(event) => onChange(event.target.value === '' ? null : event.target.value)} />
            </div>
        </FieldWrapper>
    );
}

function toUrl(path: string): string {
    if (/^https?:\/\//.test(path) || path.startsWith('/')) {
        return path;
    }

    return `/storage/${path}`;
}
