import { useEffect, useId, useRef, useState, type KeyboardEvent } from 'react';
import { requestJson } from '../../lib/http';
import { cn, useT } from '../../lib/utils';
import { FieldWrapper } from '../ui/Form';
import { Icon } from '../ui/Icon';
import type { FieldProps } from './FieldRenderer';

interface Option {
    value: string;
    label: string;
}

/**
 * A select over too many options to list (hundreds of students…): type to search on the server,
 * results come 20 at a time with "show more".
 */
export function RemoteSelectField({ field, value, onChange, path, errors }: FieldProps) {
    const t = useT();
    const id = useId();
    const listId = `${id}-list`;
    const current = value === null || value === undefined || value === '' ? null : String(value);
    const [selectedLabel, setSelectedLabel] = useState<string | null>(current ? (field.options?.[current] ?? current) : null);
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [options, setOptions] = useState<Option[]>([]);
    const [page, setPage] = useState(1);
    const [more, setMore] = useState(false);
    const [loading, setLoading] = useState(false);
    const [active, setActive] = useState(0);
    const root = useRef<HTMLDivElement>(null);
    const input = useRef<HTMLInputElement>(null);
    const error = errors[path];

    const load = async (term: string, pageNumber: number, signal?: AbortSignal) => {
        if (!field.optionsUrl) {
            return;
        }

        setLoading(true);

        try {
            const url = `${field.optionsUrl}?${new URLSearchParams({ search: term, page: String(pageNumber) })}`;
            const result = await requestJson<{ options: Option[]; more: boolean }>(url, { signal });
            setOptions((previous) => (pageNumber === 1 ? result.options : [...previous, ...result.options]));
            setMore(result.more);
            setPage(pageNumber);
        } catch (exception) {
            if ((exception as Error).name !== 'AbortError') {
                setOptions([]);
                setMore(false);
            }
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (!open) {
            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(() => load(search, 1, controller.signal), search === '' ? 0 : 250);
        setActive(0);

        return () => {
            controller.abort();
            window.clearTimeout(timer);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, search]);

    useEffect(() => {
        if (!open) {
            return;
        }

        input.current?.focus();
        const close = (event: MouseEvent) => !root.current?.contains(event.target as Node) && setOpen(false);
        document.addEventListener('mousedown', close);

        return () => document.removeEventListener('mousedown', close);
    }, [open]);

    const choose = (option: Option | null) => {
        onChange(option ? option.value : null);
        setSelectedLabel(option?.label ?? null);
        setOpen(false);
        setSearch('');
    };

    const onKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActive((index) => Math.min(index + 1, options.length - 1));
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive((index) => Math.max(index - 1, 0));
        } else if (event.key === 'Enter') {
            event.preventDefault();
            if (options[active]) {
                choose(options[active]);
            }
        } else if (event.key === 'Escape') {
            setOpen(false);
        }
    };

    return (
        <FieldWrapper id={id} label={field.label} required={field.required} help={field.help} error={error}>
            <div ref={root} className="relative">
                <div className="flex gap-2">
                    <button
                        id={id}
                        type="button"
                        aria-haspopup="listbox"
                        aria-expanded={open}
                        aria-invalid={error ? true : undefined}
                        onClick={() => setOpen((value) => !value)}
                        className={cn(
                            'flex h-10 w-full min-w-0 items-center justify-between gap-2 rounded-control border border-line-strong bg-panel px-3 text-start text-[15px] transition-colors hover:border-ink-faint focus:border-accent focus:ring-3 focus:ring-accent/15 focus:outline-none',
                            error && 'border-danger',
                        )}
                    >
                        <span className={cn('truncate', !selectedLabel && 'text-ink-faint')}>{selectedLabel ?? (field.required ? t('search') : t('none'))}</span>
                        <Icon name="chevron-down" size={16} className="shrink-0 text-ink-faint" />
                    </button>
                    {current && !field.required && (
                        <button type="button" onClick={() => choose(null)} className="grid size-10 shrink-0 place-items-center rounded-control border border-line-strong text-ink-faint hover:text-danger" title={t('none')} aria-label={t('none')}>
                            <Icon name="x" size={16} />
                        </button>
                    )}
                </div>

                {open && (
                    <div className="absolute inset-x-0 top-full z-30 mt-1 overflow-hidden rounded-control border border-line bg-panel shadow-pop">
                        <div className="border-b border-line p-2">
                            <input
                                ref={input}
                                type="search"
                                role="combobox"
                                aria-controls={listId}
                                aria-expanded="true"
                                aria-activedescendant={options[active] ? `${listId}-${active}` : undefined}
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                onKeyDown={onKeyDown}
                                placeholder={t('search')}
                                className="h-9 w-full rounded-[6px] bg-sunken px-3 text-sm focus:outline-none"
                            />
                        </div>
                        <ul id={listId} role="listbox" className="max-h-64 overflow-y-auto py-1">
                            {options.map((option, index) => (
                                <li
                                    key={option.value}
                                    id={`${listId}-${index}`}
                                    role="option"
                                    aria-selected={option.value === current}
                                    onMouseEnter={() => setActive(index)}
                                    onMouseDown={(event) => event.preventDefault()}
                                    onClick={() => choose(option)}
                                    className={cn('flex cursor-pointer items-center justify-between gap-2 px-3 py-2 text-sm', index === active && 'bg-sunken')}
                                >
                                    <span className="truncate">{option.label}</span>
                                    {option.value === current && <Icon name="check" size={15} className="shrink-0 text-accent" />}
                                </li>
                            ))}
                            {!loading && options.length === 0 && <li className="px-3 py-3 text-center text-sm text-ink-faint">{t('no_records')}</li>}
                            {loading && <li className="px-3 py-2 text-center text-sm text-ink-faint">…</li>}
                        </ul>
                        {more && !loading && (
                            <button type="button" onClick={() => load(search, page + 1)} className="w-full border-t border-line px-3 py-2 text-sm font-medium text-accent hover:bg-sunken">
                                {t('load_more')}
                            </button>
                        )}
                    </div>
                )}
            </div>
        </FieldWrapper>
    );
}
