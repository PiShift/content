import { forwardRef, type InputHTMLAttributes, type ReactNode, type SelectHTMLAttributes, type TextareaHTMLAttributes } from 'react';
import { cn } from '../../lib/utils';

const CONTROL =
    'w-full rounded-control border border-line-strong bg-panel px-3 text-[15px] text-ink placeholder:text-ink-faint transition-colors hover:border-ink-faint focus:border-accent focus:outline-none focus:ring-3 focus:ring-accent/15 aria-invalid:border-danger aria-invalid:ring-danger/15';

export function FieldWrapper({
    id,
    label,
    help,
    error,
    required,
    aside,
    children,
    className,
}: {
    id?: string;
    label?: ReactNode;
    help?: ReactNode;
    error?: string;
    required?: boolean;
    aside?: ReactNode;
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className={cn('flex min-w-0 flex-col gap-1.5', className)}>
            {(label || aside) && (
                <div className="flex items-center justify-between gap-3">
                    {label && (
                        <label htmlFor={id} className="text-[13px] font-semibold text-ink">
                            {label}
                            {required && <span className="ms-0.5 text-danger">*</span>}
                        </label>
                    )}
                    {aside}
                </div>
            )}
            {children}
            {error ? (
                <p className="text-[13px] text-danger" role="alert">
                    {error}
                </p>
            ) : (
                help && <p className="text-[13px] text-ink-faint">{help}</p>
            )}
        </div>
    );
}

export const TextInput = forwardRef<HTMLInputElement, InputHTMLAttributes<HTMLInputElement> & { invalid?: boolean }>(function TextInput(
    { className, invalid, ...props },
    ref,
) {
    return <input ref={ref} aria-invalid={invalid || undefined} className={cn(CONTROL, 'h-10', className)} {...props} />;
});

export const TextArea = forwardRef<HTMLTextAreaElement, TextareaHTMLAttributes<HTMLTextAreaElement> & { invalid?: boolean }>(function TextArea(
    { className, invalid, ...props },
    ref,
) {
    return <textarea ref={ref} aria-invalid={invalid || undefined} className={cn(CONTROL, 'min-h-20 py-2 leading-relaxed', className)} {...props} />;
});

export function SelectInput({
    options,
    placeholder,
    className,
    invalid,
    ...props
}: SelectHTMLAttributes<HTMLSelectElement> & { options: Array<{ value: string; label: string }>; placeholder?: string; invalid?: boolean }) {
    return (
        <select aria-invalid={invalid || undefined} className={cn(CONTROL, 'h-10 pe-8', className)} {...props}>
            {placeholder !== undefined && <option value="">{placeholder}</option>}
            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
    );
}

export function Switch({ checked, onChange, label, id }: { checked: boolean; onChange: (checked: boolean) => void; label: ReactNode; id?: string }) {
    return (
        <label htmlFor={id} className="inline-flex cursor-pointer items-center gap-3 text-sm text-ink">
            <button
                id={id}
                type="button"
                role="switch"
                aria-checked={checked}
                onClick={() => onChange(!checked)}
                className={cn(
                    'relative inline-flex h-6 w-10 shrink-0 items-center rounded-full transition-colors',
                    checked ? 'bg-accent' : 'bg-line-strong',
                )}
            >
                <span
                    className={cn(
                        'inline-block size-5 rounded-full bg-white shadow transition-transform',
                        checked ? 'translate-x-[18px] rtl:-translate-x-[18px]' : 'translate-x-0.5 rtl:-translate-x-0.5',
                    )}
                />
            </button>
            {label}
        </label>
    );
}

/**
 * Segmented choice for a few options (variants, style options, locales).
 */
export function Segmented({
    value,
    options,
    onChange,
    label,
}: {
    value: string | null | undefined;
    options: Array<{ value: string; label: ReactNode; hint?: string }>;
    onChange: (value: string) => void;
    label?: string;
}) {
    return (
        <div role="radiogroup" aria-label={label} className="inline-flex max-w-full flex-wrap gap-1 rounded-control bg-sunken p-1">
            {options.map((option) => (
                <button
                    key={option.value}
                    type="button"
                    role="radio"
                    aria-checked={value === option.value}
                    title={option.hint}
                    onClick={() => onChange(option.value)}
                    className={cn(
                        'inline-flex h-8 items-center gap-1.5 rounded-[6px] px-3 text-[13px] font-medium transition-colors',
                        value === option.value ? 'bg-panel text-ink shadow-panel' : 'text-ink-soft hover:text-ink',
                    )}
                >
                    {option.label}
                </button>
            ))}
        </div>
    );
}
