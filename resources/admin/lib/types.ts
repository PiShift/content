export type Translatable = Record<string, string | null | undefined>;

export interface FieldSchema {
    name: string;
    type: string;
    label: string;
    translatable: boolean;
    required: boolean;
    help: string | null;
    default: unknown;
    maxLength?: number;
    placeholder?: string | null;
    rows?: number;
    options?: Record<string, string>;
    min?: number | null;
    max?: number | null;
    integer?: boolean;
    accept?: string[];
    withLabel?: boolean;
    fields?: FieldSchema[];
    itemLabel?: string | null;
}

export interface SectionTypeSchema {
    key: string;
    label: string;
    description: string | null;
    icon: string;
    version: number;
    variants: Record<string, string>;
    styles: Record<string, Record<string, string>>;
    fields: FieldSchema[];
}

export interface SectionData {
    id: string;
    type: string;
    version?: number;
    variant?: string | null;
    style?: Record<string, string>;
    hidden?: boolean;
    data: Record<string, unknown>;
    [key: string]: unknown;
}

export interface PageDocument {
    version: number;
    sections: SectionData[];
    [key: string]: unknown;
}

export interface NavItem {
    key: string;
    label: string;
    url: string;
    icon: string;
    group: string | null;
    active: boolean;
}

export interface SharedProps {
    admin: {
        name: string;
        locale: string;
        dir: 'rtl' | 'ltr';
        locales: string[];
        contentLocales: string[];
        localeUrl: string;
        logoutUrl: string;
        siteUrl: string;
    };
    t: Record<string, string>;
    auth: { user: { id: number | string; name: string; email: string | null } | null };
    navigation: NavItem[];
    flash: { success?: string; error?: string };
    errors: Record<string, string>;
    [key: string]: unknown;
}
