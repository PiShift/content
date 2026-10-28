import { Link } from '@inertiajs/react';
import { Badge } from '../../components/ui/Badge';
import { Icon } from '../../components/ui/Icon';
import { AdminLayout } from '../../layouts/AdminLayout';
import { useT } from '../../lib/utils';

interface GlobalRow {
    key: string;
    label: string;
    icon: string;
    status: 'published' | 'draft';
    hasChanges: boolean;
    editUrl: string;
}

export default function GlobalsIndex({ globals }: { globals: GlobalRow[] }) {
    const t = useT();

    return (
        <AdminLayout title={t('globals')}>
            {globals.length === 0 ? (
                <p className="rounded-panel border border-dashed border-line-strong bg-panel py-14 text-center text-sm text-ink-faint">{t('no_globals')}</p>
            ) : (
                <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    {globals.map((global) => (
                        <li key={global.key}>
                            <Link href={global.editUrl} className="flex h-full items-center gap-3 rounded-panel border border-line bg-panel p-4 shadow-panel transition-colors hover:border-accent">
                                <span className="grid size-10 shrink-0 place-items-center rounded-control bg-accent-soft text-accent-strong">
                                    <Icon name={global.icon} />
                                </span>
                                <span className="min-w-0 flex-1 font-semibold">{global.label}</span>
                                {global.hasChanges ? (
                                    <Badge tone="warning" dot>
                                        {t('unpublished_changes')}
                                    </Badge>
                                ) : (
                                    <Badge tone={global.status === 'published' ? 'success' : 'neutral'}>{t(global.status)}</Badge>
                                )}
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </AdminLayout>
    );
}
