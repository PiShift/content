import { forwardRef, type ButtonHTMLAttributes, type ReactNode } from 'react';
import { cn } from '../../lib/utils';
import { Icon } from './Icon';

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger';
type Size = 'sm' | 'md';

const VARIANTS: Record<Variant, string> = {
    primary: 'bg-accent text-on-accent hover:bg-accent-strong border-transparent',
    secondary: 'bg-panel text-ink border-line-strong hover:bg-sunken',
    ghost: 'bg-transparent text-ink-soft border-transparent hover:bg-sunken hover:text-ink',
    danger: 'bg-panel text-danger border-line-strong hover:bg-danger-soft',
};

const SIZES: Record<Size, string> = {
    sm: 'h-8 px-2.5 text-[13px] gap-1.5',
    md: 'h-10 px-4 text-sm gap-2',
};

export interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: Variant;
    size?: Size;
    icon?: string;
    loading?: boolean;
    children?: ReactNode;
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button(
    { variant = 'secondary', size = 'md', icon, loading = false, className, children, disabled, type = 'button', ...props },
    ref,
) {
    return (
        <button
            ref={ref}
            type={type}
            disabled={disabled || loading}
            className={cn(
                'inline-flex shrink-0 items-center justify-center rounded-control border font-medium whitespace-nowrap transition-colors disabled:cursor-not-allowed disabled:opacity-55',
                VARIANTS[variant],
                SIZES[size],
                className,
            )}
            {...props}
        >
            {loading ? (
                <span className="size-4 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true" />
            ) : (
                icon && <Icon name={icon} size={size === 'sm' ? 16 : 18} />
            )}
            {children}
        </button>
    );
});

export function IconButton({ icon, label, className, ...props }: ButtonProps & { icon: string; label: string }) {
    return (
        <button
            type="button"
            title={label}
            aria-label={label}
            className={cn(
                'inline-flex size-8 shrink-0 items-center justify-center rounded-control text-ink-faint transition-colors hover:bg-sunken hover:text-ink disabled:opacity-40',
                className,
            )}
            {...props}
        >
            <Icon name={icon} size={17} />
        </button>
    );
}
