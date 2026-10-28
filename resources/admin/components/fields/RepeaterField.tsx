import { useState } from 'react';
import type { FieldSchema } from '../../lib/types';
import { cloneWithNewIds, cn, emptyItem, hasErrorsUnder, pickText, stripHtml, useT } from '../../lib/utils';
import { SortableList } from '../editor/Sortable';
import { Button, IconButton } from '../ui/Button';
import { Icon } from '../ui/Icon';
import { FieldRenderer, type FieldProps } from './FieldRenderer';

type Item = Record<string, unknown> & { _id: string };

/**
 * A list of items (cards, links, steps…): add, reorder by drag or keyboard, duplicate,
 * remove, collapse.
 */
export function RepeaterField({ field, value, onChange, locale, path, errors }: FieldProps) {
    const t = useT();
    const items = (Array.isArray(value) ? value : []) as Item[];
    const subFields = field.fields ?? [];
    const [openIds, setOpenIds] = useState<Set<string>>(() => new Set(items.length <= 2 ? items.map((item) => item._id) : []));
    const canAdd = field.max === null || field.max === undefined || items.length < field.max;
    const canRemove = items.length > (field.min ?? 0);

    const toggle = (id: string) =>
        setOpenIds((current) => {
            const next = new Set(current);
            next.has(id) ? next.delete(id) : next.add(id);

            return next;
        });

    const add = () => {
        const item = emptyItem(subFields) as Item;
        onChange([...items, item]);
        setOpenIds((current) => new Set(current).add(item._id));
    };

    const update = (index: number, patch: Record<string, unknown>) => onChange(items.map((item, i) => (i === index ? { ...item, ...patch } : item)));

    const duplicate = (index: number) => {
        const copy = cloneWithNewIds(items[index]);
        onChange([...items.slice(0, index + 1), copy, ...items.slice(index + 1)]);
        setOpenIds((current) => new Set(current).add(copy._id));
    };

    const remove = (index: number) => onChange(items.filter((_, i) => i !== index));

    const limits = [field.min ? t('min_items', { min: field.min }) : null, field.max ? t('max_items', { max: field.max }) : null].filter(Boolean).join(' · ');

    return (
        <div className="flex min-w-0 flex-col gap-2">
            <div className="flex items-baseline justify-between gap-3">
                <p className="text-[13px] font-semibold">
                    {field.label}
                    {(field.required || (field.min ?? 0) > 0) && <span className="ms-0.5 text-danger">*</span>}
                </p>
                <span className="text-xs text-ink-faint">
                    {t('items_count', { count: items.length })}
                    {limits && ` · ${limits}`}
                </span>
            </div>

            {errors[path] && (
                <p className="text-[13px] text-danger" role="alert">
                    {errors[path]}
                </p>
            )}

            <SortableList items={items} getId={(item) => item._id} onReorder={onChange}>
                {(item, index, handle) => {
                    const itemPath = `${path}.${index}`;
                    const open = openIds.has(item._id);
                    const invalid = hasErrorsUnder(errors, itemPath);

                    return (
                        <div
                            className={cn(
                                'mb-2 rounded-control border bg-panel',
                                invalid ? 'border-danger' : 'border-line',
                                handle.isDragging && 'shadow-pop',
                            )}
                        >
                            <div className="flex items-center gap-1 px-1.5 py-1.5">
                                <button
                                    type="button"
                                    className="grid size-8 cursor-grab touch-none place-items-center rounded-control text-ink-faint hover:bg-sunken active:cursor-grabbing"
                                    aria-label={t('move')}
                                    {...handle.attributes}
                                    {...handle.listeners}
                                >
                                    <Icon name="grip" size={17} />
                                </button>
                                <button type="button" onClick={() => toggle(item._id)} className="flex min-w-0 flex-1 items-center gap-2 py-1 text-start" aria-expanded={open}>
                                    <Icon name="chevron-down" size={16} className={cn('shrink-0 text-ink-faint transition-transform', !open && '-rotate-90 rtl:rotate-90')} />
                                    <span className="truncate text-sm font-medium">{itemTitle(item, field, subFields, locale) || `${t('item')} ${index + 1}`}</span>
                                    {invalid && <Icon name="alert" size={15} className="shrink-0 text-danger" />}
                                </button>
                                <IconButton icon="copy" label={t('duplicate')} onClick={() => duplicate(index)} disabled={!canAdd} />
                                <IconButton icon="trash" label={t('remove')} onClick={() => remove(index)} disabled={!canRemove} className="hover:text-danger" />
                            </div>
                            {open && (
                                <div className="flex flex-col gap-4 border-t border-line px-4 py-4">
                                    {subFields.map((subField) => (
                                        <FieldRenderer
                                            key={subField.name}
                                            field={subField}
                                            value={item[subField.name]}
                                            onChange={(next) => update(index, { [subField.name]: next })}
                                            locale={locale}
                                            path={`${itemPath}.${subField.name}`}
                                            errors={errors}
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    );
                }}
            </SortableList>

            <Button size="sm" icon="plus" onClick={add} disabled={!canAdd} className="self-start">
                {t('add_item')}
            </Button>
        </div>
    );
}

function itemTitle(item: Item, field: FieldSchema, subFields: FieldSchema[], locale: string): string {
    const labelField = field.itemLabel ?? subFields.find((sub) => ['text', 'textarea'].includes(sub.type))?.name;

    return labelField ? stripHtml(pickText(item[labelField], locale)).slice(0, 80) : '';
}
