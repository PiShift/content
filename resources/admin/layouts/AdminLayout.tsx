import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState, type ReactNode } from 'react';
import { Icon } from '../components/ui/Icon';
import { Toasts } from '../components/ui/Toasts';
import { cn, LOCALE_NAMES, useShared, useT } from '../lib/utils';
import type { NavItem } from '../lib/types';

/**
 * Admin shell: sidebar navigation (drawer on small screens), top bar, page content.
 */
export function AdminLayout({ title, actions, children, wide = false }: { title: string; actions?: ReactNode; children: ReactNode; wide?: boolean }) {
    const t = useT();
    const { admin, auth, navigation } = useShared();
    const [menuOpen, setMenuOpen] = useState(false);

    useEffect(() => router.on('navigate', () => setMenuOpen(false)), []);

    useEffect(() => {
        document.documentElement.lang = admin.locale;
        document.documentElement.dir = admin.dir;
    }, [admin.locale, admin.dir]);

    const groups = navigation.reduce<Record<string, NavItem[]>>((carry, item) => {
        (carry[item.group ?? ''] ??= []).push(item);

        return carry;
    }, {});

    return (
        <div className="min-h-dvh lg:grid lg:grid-cols-[16rem_minmax(0,1fr)]">
            <Head title={title} />

            {menuOpen && <div className="fixed inset-0 z-30 bg-ink/40 lg:hidden" onClick={() => setMenuOpen(false)} aria-hidden="true" />}

            <aside
                className={cn(
                    'fixed inset-y-0 start-0 z-40 w-72 max-w-[85vw] flex-col border-e border-line bg-panel lg:sticky lg:top-0 lg:z-auto lg:flex lg:h-dvh lg:w-auto lg:max-w-none',
                    menuOpen ? 'flex' : 'hidden',
                )}
            >
                <div className="flex h-16 items-center gap-3 border-b border-line px-5">
                    <span className="grid size-8 place-items-center rounded-control bg-accent text-sm font-bold text-on-accent">
                        {admin.name.trim().charAt(0)}
                    </span>
                    <span className="truncate text-sm leading-tight font-semibold">{admin.name}</span>
                </div>

                <nav className="flex-1 overflow-y-auto px-3 py-4" aria-label={t('menu')}>
                    {Object.entries(groups).map(([group, items]) => (
                        <div key={group} className="mb-5">
                            {group && <p className="px-3 pb-1.5 text-[11px] font-semibold tracking-wider text-ink-faint uppercase">{group}</p>}
                            <ul className="flex flex-col gap-0.5">
                                {items.map((item) => (
                                    <li key={item.key}>
                                        <Link
                                            href={item.url}
                                            className={cn(
                                                'flex h-10 items-center gap-3 rounded-control px-3 text-sm font-medium transition-colors',
                                                item.active ? 'bg-accent-soft text-accent-strong' : 'text-ink-soft hover:bg-sunken hover:text-ink',
                                            )}
                                        >
                                            <Icon name={item.icon} />
                                            <span className="min-w-0 flex-1 truncate">{item.label}</span>
                                            {item.badge !== null && item.badge > 0 && (
                                                <span className="grid h-5 min-w-5 place-items-center rounded-full bg-warning-soft px-1.5 text-[11px] font-bold text-warning">{item.badge}</span>
                                            )}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </nav>

                <div className="flex flex-col gap-1 border-t border-line p-3">
                    <a href={admin.siteUrl} target="_blank" rel="noreferrer" className="flex h-9 items-center gap-3 rounded-control px-3 text-sm text-ink-soft hover:bg-sunken hover:text-ink">
                        <Icon name="external" size={17} />
                        {t('open_site')}
                    </a>
                    <div className="flex items-center gap-1 px-1">
                        <Icon name="globe" size={17} className="mx-2 text-ink-faint" />
                        {admin.locales.map((locale) => (
                            <a
                                key={locale}
                                href={admin.localeUrl.replace('__LOCALE__', locale)}
                                className={cn(
                                    'rounded-[6px] px-2 py-1 text-[13px]',
                                    locale === admin.locale ? 'bg-sunken font-semibold text-ink' : 'text-ink-faint hover:text-ink',
                                )}
                            >
                                {LOCALE_NAMES[locale] ?? locale}
                            </a>
                        ))}
                    </div>
                    {auth.user && (
                        <div className="mt-1 flex items-center justify-between gap-2 rounded-control bg-sunken px-3 py-2">
                            <span className="min-w-0 truncate text-[13px] font-medium">{auth.user.name}</span>
                            <Link href={admin.logoutUrl} method="post" as="button" className="text-ink-faint hover:text-danger" title={t('logout')} aria-label={t('logout')}>
                                <Icon name="logout" size={17} />
                            </Link>
                        </div>
                    )}
                </div>
            </aside>

            <div className="flex min-w-0 flex-col">
                <header className="sticky top-0 z-20 flex min-h-16 flex-wrap items-center gap-3 border-b border-line bg-canvas/90 px-4 py-2.5 backdrop-blur sm:px-6">
                    <button
                        type="button"
                        className="grid size-9 place-items-center rounded-control text-ink-soft hover:bg-sunken lg:hidden"
                        onClick={() => setMenuOpen(true)}
                        aria-label={t('menu')}
                    >
                        <Icon name="menu" />
                    </button>
                    <h1 className="min-w-0 flex-1 truncate text-lg font-semibold">{title}</h1>
                    {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
                </header>

                <main className={cn('w-full flex-1 px-4 py-6 sm:px-6', !wide && 'mx-auto max-w-6xl')}>{children}</main>
            </div>

            <Toasts />
        </div>
    );
}
