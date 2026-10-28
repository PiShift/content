import type { Cell } from '../../lib/types';
import { useShared, useT } from '../../lib/utils';
import { Badge } from './Badge';
import { Icon } from './Icon';

/**
 * One cell of an admin table (collection lists, dashboard tables).
 */
export function CellValue({ cell }: { cell: Cell | undefined }) {
    const t = useT();
    const { admin } = useShared();

    if (!cell || cell.value === null || cell.value === undefined || cell.value === '') {
        return <span className="text-ink-faint">—</span>;
    }

    switch (cell.type) {
        case 'badge':
            return <Badge tone={cell.tone ?? 'neutral'}>{String(cell.value)}</Badge>;
        case 'boolean':
            return <Badge tone={cell.value ? 'success' : 'neutral'}>{cell.value ? t('yes') : t('no')}</Badge>;
        case 'image':
            return <img src={String(cell.value)} alt="" className="size-10 rounded-[6px] bg-sunken object-cover" />;
        case 'file':
            return <Icon name="file" size={18} className="text-ink-soft" />;
        case 'date':
            return <span className="whitespace-nowrap">{new Intl.DateTimeFormat(admin.locale, { dateStyle: 'medium' }).format(new Date(String(cell.value)))}</span>;
        case 'relative':
            return <span className="whitespace-nowrap">{relativeTime(String(cell.value), admin.locale)}</span>;
        default:
            return <span className="line-clamp-2">{String(cell.value)}</span>;
    }
}

function relativeTime(iso: string, locale: string): string {
    const seconds = (new Date(iso).getTime() - Date.now()) / 1000;
    const units: Array<[Intl.RelativeTimeFormatUnit, number]> = [
        ['year', 31536000],
        ['month', 2592000],
        ['day', 86400],
        ['hour', 3600],
        ['minute', 60],
    ];
    const format = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });

    for (const [unit, size] of units) {
        if (Math.abs(seconds) >= size) {
            return format.format(Math.round(seconds / size), unit);
        }
    }

    return format.format(Math.round(seconds), 'second');
}
