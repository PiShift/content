import { useId, useState } from 'react';
import { useT } from '../../lib/utils';
import { formatSize, useMediaItem } from '../media/media';
import { MediaPicker, MediaThumb } from '../media/MediaPicker';
import { Button } from '../ui/Button';
import { FieldWrapper } from '../ui/Form';
import { Icon } from '../ui/Icon';
import type { FieldProps } from './FieldRenderer';

/**
 * Image or file from the media library (stored as its id). Imported content may hold a
 * path or URL instead; it keeps working and can be replaced from the library.
 */
export function MediaField({ field, value, onChange, path, errors }: FieldProps) {
    const t = useT();
    const id = useId();
    const [picking, setPicking] = useState(false);
    const mediaId = typeof value === 'number' ? value : typeof value === 'string' && /^\d+$/.test(value) ? Number(value) : null;
    const item = useMediaItem(mediaId);
    const legacyPath = mediaId === null && typeof value === 'string' && value !== '' ? value : null;
    const type = field.type === 'image' ? 'image' : 'file';

    return (
        <FieldWrapper id={id} label={field.label} required={field.required} help={field.help} error={errors[path]}>
            <div className="flex items-center gap-3 rounded-control border border-line bg-panel p-2">
                {item ? (
                    <MediaThumb item={item} className="size-16 shrink-0 rounded-[6px]" />
                ) : legacyPath && type === 'image' ? (
                    <img src={toUrl(legacyPath)} alt="" className="size-16 shrink-0 rounded-[6px] bg-sunken object-cover" />
                ) : (
                    <span className="grid size-16 shrink-0 place-items-center rounded-[6px] bg-sunken text-ink-faint">
                        <Icon name={type === 'image' ? 'image' : 'file'} size={22} />
                    </span>
                )}
                <div className="min-w-0 flex-1">
                    {item ? (
                        <>
                            <p className="truncate text-sm font-medium">{item.name}</p>
                            <p className="text-xs text-ink-faint" dir="ltr">
                                {[item.width && item.height ? `${item.width}×${item.height}` : null, formatSize(item.size)].filter(Boolean).join(' · ')}
                            </p>
                        </>
                    ) : legacyPath ? (
                        <p className="truncate text-sm text-ink-soft" dir="ltr">
                            {legacyPath}
                        </p>
                    ) : (
                        <p className="text-sm text-ink-faint">{t('none')}</p>
                    )}
                </div>
                <div className="flex shrink-0 flex-wrap justify-end gap-1.5">
                    <Button id={id} size="sm" icon={value ? undefined : 'plus'} onClick={() => setPicking(true)}>
                        {value ? t('replace') : t('choose')}
                    </Button>
                    {value !== null && value !== undefined && value !== '' && !field.required && (
                        <Button size="sm" variant="ghost" icon="x" onClick={() => onChange(null)} aria-label={t('remove')} />
                    )}
                </div>
            </div>
            <MediaPicker open={picking} onClose={() => setPicking(false)} type={type} onPick={(picked) => onChange(field.storesPath ? picked.path : picked.id)} />
        </FieldWrapper>
    );
}

function toUrl(path: string): string {
    return /^https?:\/\//.test(path) || path.startsWith('/') ? path : `/storage/${path}`;
}
