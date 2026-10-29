const ARABIC: Record<string, string> = {
    ا: 'a', أ: 'a', إ: 'i', آ: 'a', ء: '', ؤ: 'o', ئ: 'e', ى: 'a', ة: 'a',
    ب: 'b', ت: 't', ث: 'th', ج: 'j', ح: 'h', خ: 'kh', د: 'd', ذ: 'dh', ر: 'r', ز: 'z',
    س: 's', ش: 'sh', ص: 's', ض: 'd', ط: 't', ظ: 'z', ع: 'a', غ: 'gh', ف: 'f', ق: 'q',
    ك: 'k', ل: 'l', م: 'm', ن: 'n', ه: 'h', و: 'w', ي: 'y',
    '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4', '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9',
};

/**
 * A URL slug (lowercase latin letters, digits, dashes): accents dropped, Arabic transliterated.
 */
export function slugify(text: string, maxLength = 80): string {
    return text
        .normalize('NFKD')
        .replace(/[̀-ًͯ-ٰٟـ]/g, '')
        .replace(/[؀-ۿ]/g, (letter) => ARABIC[letter] ?? '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, maxLength)
        .replace(/-+$/, '');
}

/**
 * The slug for a (possibly translated) source value, preferring a latin translation so the
 * address stays readable.
 */
export function slugFromValue(value: unknown, locales: string[]): string {
    if (typeof value === 'string') {
        return slugify(value);
    }

    if (!value || typeof value !== 'object') {
        return '';
    }

    const translations = value as Record<string, unknown>;
    const order = [...locales.filter((locale) => locale !== 'ar'), ...locales.filter((locale) => locale === 'ar'), ...Object.keys(translations)];

    for (const locale of order) {
        const slug = typeof translations[locale] === 'string' ? slugify(translations[locale] as string) : '';

        if (slug) {
            return slug;
        }
    }

    return '';
}
