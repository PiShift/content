import { EditorContent, useEditor, useEditorState, type Editor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { useEffect, useId } from 'react';
import { cn, localeDir, useT } from '../../lib/utils';
import { FieldWrapper } from '../ui/Form';
import type { FieldProps } from './FieldRenderer';
import { otherLocaleError, TranslationHint } from './FieldRenderer';

/**
 * Formatted text limited to what the site's sanitizer keeps: headings, emphasis, lists,
 * quotes and links.
 */
export function RichTextField({ field, value, onChange, locale, path, errors }: FieldProps) {
    const id = useId();
    const translations = field.translatable && value && typeof value === 'object' ? (value as Record<string, unknown>) : {};
    const current = ((field.translatable ? translations[locale] : value) as string | null) ?? '';
    const error = field.translatable ? (errors[`${path}.${locale}`] ?? otherLocaleError(errors, path, locale)) : errors[path];

    const set = (html: string) => {
        const next = html === '<p></p>' ? '' : html;
        onChange(field.translatable ? { ...translations, [locale]: next } : next);
    };

    return (
        <FieldWrapper
            id={id}
            label={field.label}
            required={field.required}
            help={field.help}
            error={error}
            aside={field.translatable ? <TranslationHint value={value} locale={locale} /> : undefined}
        >
            {/* Remount per locale so each language keeps its own editor history. */}
            <RichTextEditor key={locale} id={id} html={current} dir={field.translatable ? localeDir(locale) : 'auto'} invalid={Boolean(error)} onChange={set} />
        </FieldWrapper>
    );
}

function RichTextEditor({ id, html, dir, invalid, onChange }: { id: string; html: string; dir: 'rtl' | 'ltr' | 'auto'; invalid: boolean; onChange: (html: string) => void }) {
    const editor = useEditor({
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3] },
                code: false,
                codeBlock: false,
                horizontalRule: false,
                link: { openOnClick: false, autolink: true, defaultProtocol: 'https' },
            }),
        ],
        content: html,
        immediatelyRender: true,
        editorProps: {
            attributes: {
                id,
                dir,
                class: 'content-prose min-h-32 px-3 py-2.5 focus:outline-none',
            },
        },
        onUpdate: ({ editor }) => onChange(editor.getHTML()),
    });

    // Follow outside changes (e.g. a restored revision) without fighting the cursor.
    useEffect(() => {
        if (editor && !editor.isFocused && editor.getHTML() !== html && !(html === '' && editor.isEmpty)) {
            editor.commands.setContent(html, { emitUpdate: false });
        }
    }, [editor, html]);

    return (
        <div
            className={cn(
                'overflow-hidden rounded-control border bg-panel transition-colors focus-within:border-accent focus-within:ring-3 focus-within:ring-accent/15',
                invalid ? 'border-danger' : 'border-line-strong',
            )}
        >
            {editor && <Toolbar editor={editor} />}
            <EditorContent editor={editor} />
        </div>
    );
}

function Toolbar({ editor }: { editor: Editor }) {
    const t = useT();
    const state = useEditorState({
        editor,
        selector: ({ editor }) => ({
            bold: editor.isActive('bold'),
            italic: editor.isActive('italic'),
            underline: editor.isActive('underline'),
            h2: editor.isActive('heading', { level: 2 }),
            h3: editor.isActive('heading', { level: 3 }),
            bullet: editor.isActive('bulletList'),
            ordered: editor.isActive('orderedList'),
            quote: editor.isActive('blockquote'),
            link: editor.isActive('link'),
            canUndo: editor.can().undo(),
            canRedo: editor.can().redo(),
        }),
    });

    const setLink = () => {
        const previous = (editor.getAttributes('link').href as string | undefined) ?? '';
        const url = window.prompt(t('link_prompt'), previous);

        if (url === null) {
            return;
        }

        if (url.trim() === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
        } else {
            editor.chain().focus().extendMarkRange('link').setLink({ href: url.trim() }).run();
        }
    };

    const buttons: Array<{ label: string; text: string; active?: boolean; disabled?: boolean; run: () => void; className?: string }> = [
        { label: t('bold'), text: 'B', active: state.bold, run: () => editor.chain().focus().toggleBold().run(), className: 'font-bold' },
        { label: t('italic'), text: 'I', active: state.italic, run: () => editor.chain().focus().toggleItalic().run(), className: 'italic font-serif' },
        { label: t('underline'), text: 'U', active: state.underline, run: () => editor.chain().focus().toggleUnderline().run(), className: 'underline' },
        { label: t('heading_2'), text: 'H2', active: state.h2, run: () => editor.chain().focus().toggleHeading({ level: 2 }).run() },
        { label: t('heading_3'), text: 'H3', active: state.h3, run: () => editor.chain().focus().toggleHeading({ level: 3 }).run() },
        { label: t('bullet_list'), text: '•', active: state.bullet, run: () => editor.chain().focus().toggleBulletList().run() },
        { label: t('ordered_list'), text: '1.', active: state.ordered, run: () => editor.chain().focus().toggleOrderedList().run() },
        { label: t('quote'), text: '“', active: state.quote, run: () => editor.chain().focus().toggleBlockquote().run() },
        { label: t('link'), text: '🔗', active: state.link, run: setLink },
        { label: t('undo'), text: '↶', disabled: !state.canUndo, run: () => editor.chain().focus().undo().run() },
        { label: t('redo'), text: '↷', disabled: !state.canRedo, run: () => editor.chain().focus().redo().run() },
    ];

    return (
        <div className="flex flex-wrap gap-0.5 border-b border-line bg-canvas px-1.5 py-1" role="toolbar" dir="ltr">
            {buttons.map((button) => (
                <button
                    key={button.label}
                    type="button"
                    title={button.label}
                    aria-label={button.label}
                    aria-pressed={button.active}
                    disabled={button.disabled}
                    onMouseDown={(event) => event.preventDefault()}
                    onClick={button.run}
                    className={cn(
                        'grid h-8 min-w-8 place-items-center rounded-[6px] px-1.5 text-[13px] transition-colors disabled:opacity-35',
                        button.active ? 'bg-accent-soft text-accent-strong' : 'text-ink-soft hover:bg-sunken hover:text-ink',
                        button.className,
                    )}
                >
                    {button.text}
                </button>
            ))}
        </div>
    );
}
