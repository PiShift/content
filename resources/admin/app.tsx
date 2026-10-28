import './admin.css';
import { createInertiaApp } from '@inertiajs/react';
import type { ComponentType } from 'react';
import { createRoot } from 'react-dom/client';

type PageModules = Record<string, () => Promise<unknown>>;

const packagePages = import.meta.glob('./pages/**/*.tsx') as PageModules;

/**
 * Boots the admin. Inertia components named "App/Foo/Bar" come from the project's own
 * pages (passed in `pages`, globbed from the project's resources/admin/pages); every other
 * name is a package page.
 */
export function createContentAdmin({ pages = {} }: { pages?: PageModules } = {}) {
    return createInertiaApp({
        title: (title) => title,
        resolve: async (name) => {
            const isProjectPage = name.startsWith('App/');
            const modules = isProjectPage ? pages : packagePages;
            const path = isProjectPage ? `./pages/${name.slice(4)}.tsx` : `./pages/${name}.tsx`;
            const loader = modules[path];

            if (!loader) {
                throw new Error(`Admin page not found: ${name}`);
            }

            const module = (await loader()) as { default: ComponentType };

            return module.default;
        },
        setup({ el, App, props }) {
            createRoot(el).render(<App {...props} />);
        },
        progress: { color: '#1f6f45' },
    });
}
