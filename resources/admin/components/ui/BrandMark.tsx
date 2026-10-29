import { cn, useShared } from '../../lib/utils';

/**
 * The project logo, or the first letter of the site name on the accent colour.
 */
export function BrandMark({ size }: { size: 'sm' | 'lg' }) {
    const { admin } = useShared();

    if (admin.logo) {
        return <img src={admin.logo} alt={admin.name} className={cn('w-auto max-w-40 object-contain', size === 'sm' ? 'h-8' : 'h-14')} />;
    }

    return (
        <span
            className={cn(
                'grid place-items-center bg-accent font-bold text-on-accent',
                size === 'sm' ? 'size-8 rounded-control text-sm' : 'size-11 rounded-panel text-lg',
            )}
        >
            {admin.name.trim().charAt(0)}
        </span>
    );
}
