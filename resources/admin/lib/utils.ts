import { usePage } from '@inertiajs/react';
import type { FieldSchema, SharedProps } from './types';

export function cn(...classes: Array<string | false | null | undefined>): string {
    return classes.filter(Boolean).join(' ');
}

export function useShared(): SharedProps {
    return usePage().props as unknown as SharedProps;
}

/**
 * Admin interface strings, with ":name" placeholders.
 */
export function useT(): (key: string, replace?: Record<string, string | number>) => string {
    const { t } = useShared();

    return (key, replace = {}) =>
        Object.entries(replace).reduce((text, [name, value]) => text.replaceAll(`:${name}`, String(value)), t?.[key] ?? key);
}

export function uid(): string {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return crypto.randomUUID();
    }

    return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;
}

export const LOCALE_NAMES: Record<string, string> = { ar: 'العربية', fr: 'Français', en: 'English' };

export function localeDir(locale: string): 'rtl' | 'ltr' {
    return locale === 'ar' ? 'rtl' : 'ltr';
}

/**
 * Text of a translatable value in a locale, falling back to the first filled locale.
 */
export function pickText(value: unknown, locale: string): string {
    if (typeof value === 'string') {
        return value;
    }

    if (value && typeof value === 'object') {
        const record = value as Record<string, unknown>;
        const text = record[locale] ?? Object.values(record).find((entry) => typeof entry === 'string' && entry !== '');

        return typeof text === 'string' ? text : '';
    }

    return '';
}

export function stripHtml(html: string): string {
    return html.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
}

/**
 * Empty value for a field, used when adding sections and repeater items.
 */
export function emptyValue(field: FieldSchema): unknown {
    switch (field.type) {
        case 'repeater':
            return Array.from({ length: field.min ?? 0 }, () => emptyItem(field.fields ?? []));
        case 'toggle':
            return Boolean(field.default);
        case 'link':
            return { url: '', label: {}, new_tab: false };
        default:
            if (field.default !== null && field.default !== undefined) {
                return structuredClone(field.default);
            }

            return field.translatable ? {} : null;
    }
}

export function emptyItem(fields: FieldSchema[]): Record<string, unknown> {
    return Object.fromEntries([['_id', uid()], ...fields.map((field) => [field.name, emptyValue(field)])]);
}

/**
 * Deep copy with fresh ids for the copy and every nested repeater item.
 */
export function cloneWithNewIds<T>(value: T): T {
    const copy = structuredClone(value);

    const visit = (node: unknown): void => {
        if (Array.isArray(node)) {
            node.forEach(visit);
        } else if (node && typeof node === 'object') {
            const record = node as Record<string, unknown>;
            if ('_id' in record) {
                record._id = uid();
            }
            Object.values(record).forEach(visit);
        }
    };

    visit(copy);

    return copy;
}

/**
 * True when a translatable value misses the locale while another locale is filled.
 */
export function missingTranslation(value: unknown, locale: string): boolean {
    if (!value || typeof value !== 'object' || Array.isArray(value)) {
        return false;
    }

    const record = value as Record<string, unknown>;
    const filled = Object.values(record).some((entry) => typeof entry === 'string' && entry.trim() !== '');

    return filled && !(typeof record[locale] === 'string' && (record[locale] as string).trim() !== '');
}

export function hasErrorsUnder(errors: Record<string, string>, prefix: string): boolean {
    return Object.keys(errors).some((key) => key === prefix || key.startsWith(`${prefix}.`));
}

export function firstErrorUnder(errors: Record<string, string>, prefix: string): string | undefined {
    const key = Object.keys(errors).find((key) => key === prefix || key.startsWith(`${prefix}.`));

    return key ? errors[key] : undefined;
}
