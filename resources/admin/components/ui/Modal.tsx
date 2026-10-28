import { useEffect, useRef, type ReactNode } from 'react';
import { cn, useT } from '../../lib/utils';
import { Button, IconButton } from './Button';

/**
 * Dialog built on <dialog>: focus trap, Escape and backdrop click come from the browser.
 */
export function Modal({
    open,
    onClose,
    title,
    children,
    footer,
    size = 'md',
}: {
    open: boolean;
    onClose: () => void;
    title: ReactNode;
    children: ReactNode;
    footer?: ReactNode;
    size?: 'sm' | 'md' | 'lg';
}) {
    const t = useT();
    const ref = useRef<HTMLDialogElement>(null);

    useEffect(() => {
        const dialog = ref.current;
        if (!dialog) {
            return;
        }

        if (open && !dialog.open) {
            dialog.showModal();
        } else if (!open && dialog.open) {
            dialog.close();
        }
    }, [open]);

    return (
        <dialog
            ref={ref}
            onClose={onClose}
            onCancel={(event) => {
                event.preventDefault();
                onClose();
            }}
            onClick={(event) => event.target === ref.current && onClose()}
            className={cn(
                'm-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] overflow-hidden rounded-panel bg-panel p-0 text-ink shadow-pop backdrop:bg-ink/40',
                { sm: 'max-w-md', md: 'max-w-xl', lg: 'max-w-3xl' }[size],
            )}
        >
            {open && (
                <div className="flex max-h-[calc(100dvh-2rem)] flex-col">
                    <header className="flex items-center justify-between gap-4 border-b border-line px-5 py-3.5">
                        <h2 className="text-base font-semibold">{title}</h2>
                        <IconButton icon="x" label={t('close')} onClick={onClose} />
                    </header>
                    <div className="overflow-y-auto px-5 py-4">{children}</div>
                    {footer && <footer className="flex flex-wrap justify-end gap-2 border-t border-line bg-canvas px-5 py-3">{footer}</footer>}
                </div>
            )}
        </dialog>
    );
}

export function ConfirmModal({
    open,
    onClose,
    onConfirm,
    title,
    message,
    confirmLabel,
}: {
    open: boolean;
    onClose: () => void;
    onConfirm: () => void;
    title: ReactNode;
    message: ReactNode;
    confirmLabel: string;
}) {
    const t = useT();

    return (
        <Modal
            open={open}
            onClose={onClose}
            title={title}
            size="sm"
            footer={
                <>
                    <Button onClick={onClose}>{t('cancel')}</Button>
                    <Button
                        variant="danger"
                        onClick={() => {
                            onConfirm();
                            onClose();
                        }}
                    >
                        {confirmLabel}
                    </Button>
                </>
            }
        >
            <p className="text-sm text-ink-soft">{message}</p>
        </Modal>
    );
}
