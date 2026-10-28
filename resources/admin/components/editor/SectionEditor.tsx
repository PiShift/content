import type { SectionData, SectionTypeSchema } from '../../lib/types';
import { useT } from '../../lib/utils';
import { FieldRenderer } from '../fields/FieldRenderer';
import { Button } from '../ui/Button';
import { FieldWrapper, Segmented } from '../ui/Form';
import { Icon } from '../ui/Icon';

/**
 * The form of one section: layout variant, style options, then its fields.
 */
export function SectionEditor({
    section,
    index,
    type,
    locale,
    errors,
    onChange,
    onRemove,
}: {
    section: SectionData;
    index: number;
    type: SectionTypeSchema | undefined;
    locale: string;
    errors: Record<string, string>;
    onChange: (section: SectionData) => void;
    onRemove: () => void;
}) {
    const t = useT();

    if (!type) {
        return (
            <div className="flex flex-col items-start gap-3 rounded-panel border border-warning/40 bg-warning-soft p-5">
                <p className="flex items-center gap-2 font-semibold text-warning">
                    <Icon name="alert" />
                    {t('unknown_section')} · <code dir="ltr">{section.type}</code>
                </p>
                <p className="text-sm text-ink-soft">{t('unknown_section_help')}</p>
                <Button variant="danger" size="sm" icon="trash" onClick={onRemove}>
                    {t('remove')}
                </Button>
            </div>
        );
    }

    const variants = Object.entries(type.variants);
    const styles = Object.entries(type.styles);

    return (
        <div className="flex flex-col gap-5">
            <div className="flex items-start gap-3">
                <span className="grid size-10 shrink-0 place-items-center rounded-control bg-accent-soft text-accent-strong">
                    <Icon name={type.icon} />
                </span>
                <div className="min-w-0">
                    <h2 className="text-base font-semibold">{type.label}</h2>
                    {type.description && <p className="text-sm text-ink-faint">{type.description}</p>}
                </div>
            </div>

            {(variants.length > 0 || styles.length > 0) && (
                <div className="flex flex-col gap-4 rounded-control bg-canvas p-4">
                    {variants.length > 0 && (
                        <FieldWrapper label={t('layout')} error={errors[`sections.${index}.variant`]}>
                            <Segmented
                                label={t('layout')}
                                value={section.variant ?? variants[0][0]}
                                options={variants.map(([value, label]) => ({ value, label }))}
                                onChange={(variant) => onChange({ ...section, variant })}
                            />
                        </FieldWrapper>
                    )}
                    {styles.map(([option, values]) => (
                        <FieldWrapper key={option} label={styleLabel(option, t)} error={errors[`sections.${index}.style.${option}`]}>
                            <Segmented
                                label={option}
                                value={section.style?.[option] ?? Object.keys(values)[0]}
                                options={Object.entries(values).map(([value, label]) => ({ value, label }))}
                                onChange={(value) => onChange({ ...section, style: { ...(section.style ?? {}), [option]: value } })}
                            />
                        </FieldWrapper>
                    ))}
                </div>
            )}

            <div className="flex flex-col gap-5">
                {type.fields.map((field) => (
                    <FieldRenderer
                        key={field.name}
                        field={field}
                        value={section.data?.[field.name]}
                        onChange={(value) => onChange({ ...section, data: { ...section.data, [field.name]: value } })}
                        locale={locale}
                        path={`sections.${index}.data.${field.name}`}
                        errors={errors}
                    />
                ))}
            </div>
        </div>
    );
}

function styleLabel(option: string, t: (key: string) => string): string {
    const translated = t(`style_${option}`);

    return translated === `style_${option}` ? option.charAt(0).toUpperCase() + option.slice(1) : translated;
}
