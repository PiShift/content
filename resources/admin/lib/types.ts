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
    visibleWhen?: { field: string; values: string[] } | null;
    storesPath?: boolean;
    readonly?: boolean;
    inputType?: 'text' | 'email' | 'password';
    /** Sibling field a slug is generated from. */
    slugFrom?: string | null;
    maxSize?: number;
}

export interface CollectionSchema {
    key: string;
    label: string;
    singularLabel: string;
    icon: string;
    titleField: string;
    fields: FieldSchema[];
    columns: Record<string, string>;
    filters: Record<string, Record<string, string>>;
    searchable: boolean;
    badges: Record<string, Record<string, Tone>>;
    canCreate: boolean;
}

export type Tone = 'neutral' | 'info' | 'success' | 'warning' | 'danger';

export interface Cell {
    type: string;
    value: unknown;
    tone?: Tone;
}

export interface RecordAction {
    key: string;
    label: string;
    icon: string;
    tone: 'primary' | 'neutral' | 'danger';
    confirm: string | null;
    url: string;
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
    badge: number | null;
}

export interface SharedProps {
    admin: {
        name: string;
        /** Project logo URL (config content.admin.brand.logo), or null for the name's first letter. */
        logo: string | null;
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
