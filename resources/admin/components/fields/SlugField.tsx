import { useEffect, useId, useState } from 'react';
import { slugFromValue } from '../../lib/slug';
import { useShared, useT } from '../../lib/utils';
import { Button } from '../ui/Button';
import { FieldWrapper, TextInput } from '../ui/Form';
import type { FieldProps } from './FieldRenderer';

/**
 * A slug that follows its source field (e.g. the title) while empty or reset, until the editor
 * unlocks it. A saved slug stays locked, so published links keep working.
 */
export function SlugField({ field, value, onChange, path, errors, siblings }: FieldProps) {
    const t = useT();
    const id = useId();
    const { admin } = useShared();
    const current = typeof value === 'string' ? value : '';
    const generated = slugFromValue(siblings?.[field.slugFrom ?? 'title'], admin.contentLocales);
    const [mode, setMode] = useState<'auto' | 'locked' | 'editing'>(() => (current === '' ? 'auto' : 'locked'));
    const auto = mode === 'auto';

    useEffect(() => {
        if (auto && generated !== current) {
            onChange(generated);
        }
    }, [auto, generated, current, onChange]);

    return (
        <FieldWrapper id={id} label={field.label} required={field.required} help={auto ? t('slug_auto') : field.help} error={errors[path]}>
            <div className="flex items-center gap-2">
                <TextInput
                    id={id}
                    dir="ltr"
                    readOnly={mode !== 'editing'}
                    invalid={Boolean(errors[path])}
                    placeholder={field.placeholder ?? undefined}
                    maxLength={field.maxLength}
                    value={current}
                    onChange={(event) => onChange(event.target.value.toLowerCase().replace(/[^a-z0-9-]+/g, '-').replace(/-{2,}/g, '-'))}
                    className={mode !== 'editing' ? 'bg-sunken text-ink-soft' : undefined}
                />
                {mode !== 'editing' ? (
                    <Button variant="secondary" icon="pencil" onClick={() => setMode('editing')}>
                        {t('slug_edit')}
                    </Button>
                ) : (
                    <Button variant="secondary" icon="refresh" disabled={!generated} title={t('slug_reset')} aria-label={t('slug_reset')} onClick={() => setMode('auto')} />
                )}
            </div>
        </FieldWrapper>
    );
}
