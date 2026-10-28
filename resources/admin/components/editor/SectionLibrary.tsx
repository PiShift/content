import { useMemo, useState } from 'react';
import type { SectionTypeSchema } from '../../lib/types';
import { useT } from '../../lib/utils';
import { TextInput } from '../ui/Form';
import { Icon } from '../ui/Icon';
import { Modal } from '../ui/Modal';

export function SectionLibrary({
    open,
    onClose,
    types,
    onPick,
}: {
    open: boolean;
    onClose: () => void;
    types: SectionTypeSchema[];
    onPick: (type: SectionTypeSchema) => void;
}) {
    const t = useT();
    const [query, setQuery] = useState('');

    const shown = useMemo(() => {
        const needle = query.trim().toLowerCase();

        return needle === '' ? types : types.filter((type) => `${type.label} ${type.description ?? ''} ${type.key}`.toLowerCase().includes(needle));
    }, [types, query]);

    return (
        <Modal open={open} onClose={onClose} title={t('section_library')} size="lg">
            {types.length > 6 && (
                <TextInput className="mb-4" type="search" value={query} onChange={(event) => setQuery(event.target.value)} aria-label={t('section_library')} />
            )}
            <div className="grid gap-2.5 sm:grid-cols-2">
                {shown.map((type) => (
                    <button
                        key={type.key}
                        type="button"
                        onClick={() => {
                            onPick(type);
                            onClose();
                        }}
                        className="flex items-start gap-3 rounded-control border border-line bg-panel p-3.5 text-start transition-colors hover:border-accent hover:bg-accent-soft/40"
                    >
                        <span className="grid size-9 shrink-0 place-items-center rounded-control bg-sunken text-ink-soft">
                            <Icon name={type.icon} />
                        </span>
                        <span className="min-w-0">
                            <span className="block text-sm font-semibold">{type.label}</span>
                            {type.description && <span className="mt-0.5 block text-[13px] leading-snug text-ink-faint">{type.description}</span>}
                        </span>
                    </button>
                ))}
            </div>
        </Modal>
    );
}
