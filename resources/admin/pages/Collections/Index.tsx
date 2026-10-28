import { Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Badge } from '../../components/ui/Badge';
import { Button } from '../../components/ui/Button';
import { SelectInput, TextInput } from '../../components/ui/Form';
import { Icon } from '../../components/ui/Icon';
import { AdminLayout } from '../../layouts/AdminLayout';
import type { CollectionSchema } from '../../lib/types';
import { cn, useShared, useT } from '../../lib/utils';

interface Cell {
    type: string;
    value: unknown;
}

interface Row {
    id: string | number;
    cells: Record<string, Cell>;
    editUrl: string;
}

interface Paginated {
    data: Row[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

export default function CollectionIndex({
    collection,
    records,
    query,
    urls,
}: {
    collection: CollectionSchema;
    records: Paginated;
    query: { search: string; filters: Record<string, string> };
    urls: { index: string; create: string };
}) {
    const t = useT();
    const [search, setSearch] = useState(query.search);
    const columns = Object.entries(collection.columns);

    const visit = (params: { search?: string; filters?: Record<string, string> }) =>
        router.get(urls.index, { search: params.search ?? search, filters: params.filters ?? query.filters }, { preserveState: true, preserveScroll: true, replace: true });

    useEffect(() => {
        if (search === query.search) {
            return;
        }

        const timer = window.setTimeout(() => visit({ search }), 300);

        return () => window.clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    return (
        <AdminLayout
            title={collection.label}
            actions={
                <Link href={urls.create}>
                    <Button variant="primary" icon="plus" tabIndex={-1}>
                        {t('new_record')}
                    </Button>
                </Link>
            }
        >
            <div className="flex flex-col gap-4">
                {(collection.searchable || Object.keys(collection.filters).length > 0) && (
                    <div className="flex flex-wrap items-center gap-3">
                        {collection.searchable && (
                            <TextInput type="search" value={search} onChange={(event) => setSearch(event.target.value)} placeholder={t('search')} className="max-w-xs" />
                        )}
                        {Object.entries(collection.filters).map(([field, options]) => (
                            <SelectInput
                                key={field}
                                aria-label={collection.fields.find((candidate) => candidate.name === field)?.label ?? field}
                                className="w-auto min-w-40"
                                placeholder={`${collection.fields.find((candidate) => candidate.name === field)?.label ?? field} · ${t('filter_all')}`}
                                options={Object.entries(options).map(([value, label]) => ({ value, label }))}
                                value={query.filters[field] ?? ''}
                                onChange={(event) => visit({ filters: { ...query.filters, [field]: event.target.value } })}
                            />
                        ))}
                        <span className="ms-auto text-sm text-ink-faint">{t('results', { count: records.total })}</span>
                    </div>
                )}

                {records.data.length === 0 ? (
                    <p className="rounded-panel border border-dashed border-line-strong bg-panel py-14 text-center text-sm text-ink-faint">{t('no_records')}</p>
                ) : (
                    <div className="overflow-hidden rounded-panel border border-line bg-panel shadow-panel">
                        <table className="hidden w-full text-sm md:table">
                            <thead className="bg-canvas text-start text-xs font-semibold tracking-wide text-ink-faint uppercase">
                                <tr>
                                    {columns.map(([name, label]) => (
                                        <th key={name} className="px-4 py-3 text-start font-semibold">
                                            {label}
                                        </th>
                                    ))}
                                    <th className="w-10" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-line">
                                {records.data.map((row) => (
                                    <tr key={row.id} className="cursor-pointer transition-colors hover:bg-canvas" onClick={() => router.get(row.editUrl)}>
                                        {columns.map(([name], index) => (
                                            <td key={name} className={cn('px-4 py-3 align-middle', index === 0 && 'font-medium')}>
                                                <CellValue cell={row.cells[name]} />
                                            </td>
                                        ))}
                                        <td className="px-3 text-ink-faint">
                                            <Link href={row.editUrl} aria-label={t('settings')}>
                                                <Icon name="chevron-right" size={16} className="rtl:rotate-180" />
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        <ul className="divide-y divide-line md:hidden">
                            {records.data.map((row) => (
                                <li key={row.id}>
                                    <Link href={row.editUrl} className="flex flex-col gap-1 px-4 py-3">
                                        {columns.map(([name, label], index) => (
                                            <span key={name} className={cn('flex items-center gap-2 text-sm', index === 0 ? 'font-semibold' : 'text-ink-soft')}>
                                                {index > 0 && <span className="text-xs text-ink-faint">{label}:</span>}
                                                <CellValue cell={row.cells[name]} />
                                            </span>
                                        ))}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {(records.prev_page_url || records.next_page_url) && (
                    <div className="flex items-center justify-center gap-3">
                        <Button size="sm" icon="chevron-left" disabled={!records.prev_page_url} onClick={() => records.prev_page_url && router.get(records.prev_page_url)} className="rtl:[&>svg]:rotate-180" />
                        <span className="text-sm text-ink-soft" dir="ltr">
                            {records.current_page} / {records.last_page}
                        </span>
                        <Button size="sm" icon="chevron-right" disabled={!records.next_page_url} onClick={() => records.next_page_url && router.get(records.next_page_url)} className="rtl:[&>svg]:rotate-180" />
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}

function CellValue({ cell }: { cell: Cell | undefined }) {
    const t = useT();
    const { admin } = useShared();

    if (!cell || cell.value === null || cell.value === undefined || cell.value === '') {
        return <span className="text-ink-faint">—</span>;
    }

    switch (cell.type) {
        case 'boolean':
            return <Badge tone={cell.value ? 'success' : 'neutral'}>{cell.value ? t('yes') : t('no')}</Badge>;
        case 'image':
            return <img src={String(cell.value)} alt="" className="size-10 rounded-[6px] bg-sunken object-cover" />;
        case 'file':
            return <Icon name="file" size={18} className="text-ink-soft" />;
        case 'date':
            return <span>{new Intl.DateTimeFormat(admin.locale, { dateStyle: 'medium' }).format(new Date(String(cell.value)))}</span>;
        default:
            return <span className="line-clamp-2">{String(cell.value)}</span>;
    }
}
