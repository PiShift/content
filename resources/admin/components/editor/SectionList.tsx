import type { SectionData, SectionTypeSchema } from '../../lib/types';
import { cn, hasErrorsUnder, pickText, stripHtml, useT } from '../../lib/utils';
import { IconButton } from '../ui/Button';
import { Icon } from '../ui/Icon';
import { SortableList } from './Sortable';

export function SectionList({
    sections,
    types,
    selectedId,
    locale,
    errors,
    onSelect,
    onReorder,
    onToggleHidden,
    onDuplicate,
    onRemove,
}: {
    sections: SectionData[];
    types: Record<string, SectionTypeSchema>;
    selectedId: string | null;
    locale: string;
    errors: Record<string, string>;
    onSelect: (id: string) => void;
    onReorder: (sections: SectionData[]) => void;
    onToggleHidden: (id: string) => void;
    onDuplicate: (id: string) => void;
    onRemove: (id: string) => void;
}) {
    const t = useT();

    if (sections.length === 0) {
        return <p className="rounded-control border border-dashed border-line-strong px-4 py-6 text-center text-sm text-ink-faint">{t('no_sections')}</p>;
    }

    return (
        <SortableList items={sections} getId={(section) => section.id} onReorder={onReorder}>
            {(section, index, handle) => {
                const type = types[section.type];
                const selected = section.id === selectedId;
                const invalid = hasErrorsUnder(errors, `sections.${index}`);

                return (
                    <div
                        className={cn(
                            'group mb-1.5 flex items-center gap-1 rounded-control border bg-panel ps-1 pe-1.5 transition-colors',
                            selected ? 'border-accent ring-2 ring-accent/15' : invalid ? 'border-danger' : 'border-line hover:border-line-strong',
                            handle.isDragging && 'shadow-pop',
                            section.hidden && 'bg-sunken',
                        )}
                    >
                        <button
                            type="button"
                            className="grid size-8 shrink-0 cursor-grab touch-none place-items-center rounded-control text-ink-faint hover:bg-sunken active:cursor-grabbing"
                            aria-label={t('move')}
                            {...handle.attributes}
                            {...handle.listeners}
                        >
                            <Icon name="grip" size={17} />
                        </button>
                        <button type="button" onClick={() => onSelect(section.id)} className="flex min-w-0 flex-1 items-center gap-2.5 py-2.5 text-start" aria-current={selected || undefined}>
                            <Icon name={type?.icon ?? 'alert'} size={17} className={cn('shrink-0', type ? 'text-ink-soft' : 'text-warning')} />
                            <span className="min-w-0 flex-1">
                                <span className={cn('block truncate text-sm font-medium', section.hidden && 'text-ink-faint line-through')}>{type?.label ?? t('unknown_section')}</span>
                                <span className="block truncate text-xs text-ink-faint">{summary(section, type, locale)}</span>
                            </span>
                            {invalid && <Icon name="alert" size={15} className="shrink-0 text-danger" />}
                        </button>
                        <div className="flex shrink-0 items-center opacity-100 transition-opacity lg:opacity-0 lg:group-focus-within:opacity-100 lg:group-hover:opacity-100">
                            <IconButton icon={section.hidden ? 'eye-off' : 'eye'} label={section.hidden ? t('show') : t('hide')} onClick={() => onToggleHidden(section.id)} />
                            <IconButton icon="copy" label={t('duplicate')} onClick={() => onDuplicate(section.id)} disabled={!type} />
                            <IconButton icon="trash" label={t('remove')} onClick={() => onRemove(section.id)} className="hover:text-danger" />
                        </div>
                    </div>
                );
            }}
        </SortableList>
    );
}

function summary(section: SectionData, type: SectionTypeSchema | undefined, locale: string): string {
    if (!type) {
        return section.type;
    }

    const textField = type.fields.find((field) => ['text', 'textarea', 'rich-text'].includes(field.type));

    return textField ? stripHtml(pickText(section.data?.[textField.name], locale)).slice(0, 70) : '';
}
