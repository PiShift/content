import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { requestJson } from '../../lib/http';
import { useShared, useT } from '../../lib/utils';
import { Button } from '../ui/Button';
import { Modal } from '../ui/Modal';

interface Revision {
    id: number;
    createdAt: string | null;
    user: string | null;
    sections: number;
}

export function RevisionsModal({ open, onClose, listUrl, restoreUrl, onBeforeRestore }: { open: boolean; onClose: () => void; listUrl: string; restoreUrl: string; onBeforeRestore: () => void }) {
    const t = useT();
    const { admin } = useShared();
    const [revisions, setRevisions] = useState<Revision[] | null>(null);

    useEffect(() => {
        if (open) {
            setRevisions(null);
            requestJson<Revision[]>(listUrl).then(setRevisions, () => setRevisions([]));
        }
    }, [open, listUrl]);

    const format = (iso: string | null) => (iso ? new Intl.DateTimeFormat(admin.locale, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(iso)) : '—');

    return (
        <Modal open={open} onClose={onClose} title={t('history')}>
            <p className="mb-4 text-sm text-ink-soft">{t('restore_help')}</p>
            {revisions === null ? (
                <div className="grid place-items-center py-8">
                    <span className="size-5 animate-spin rounded-full border-2 border-accent border-t-transparent" />
                </div>
            ) : revisions.length === 0 ? (
                <p className="py-6 text-center text-sm text-ink-faint">{t('no_revisions')}</p>
            ) : (
                <ol className="divide-y divide-line rounded-control border border-line">
                    {revisions.map((revision, index) => (
                        <li key={revision.id} className="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                            <div>
                                <p className="text-sm font-medium">
                                    {format(revision.createdAt)}
                                    {index === 0 && <span className="ms-2 text-xs font-semibold text-accent">· {t('published')}</span>}
                                </p>
                                <p className="text-xs text-ink-faint">
                                    {revision.user ? `${t('by')} ${revision.user} · ` : ''}
                                    {t('sections')}: {revision.sections}
                                </p>
                            </div>
                            <Button
                                size="sm"
                                icon="undo"
                                onClick={() => {
                                    onBeforeRestore();
                                    // Reload the editor from the restored draft instead of keeping its local state.
                                    router.post(restoreUrl.replace('__ID__', String(revision.id)), {}, { preserveState: false, preserveScroll: false });
                                    onClose();
                                }}
                            >
                                {t('restore')}
                            </Button>
                        </li>
                    ))}
                </ol>
            )}
        </Modal>
    );
}
