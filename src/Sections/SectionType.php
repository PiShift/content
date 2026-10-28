<?php

namespace PiShift\Content\Sections;

use Illuminate\Support\Str;
use PiShift\Content\Fields\Field;
use PiShift\Content\Fields\FieldSet;
use PiShift\Content\Pages\ResolvedPage;

/**
 * A kind of section the admin can add to a page. Projects define one class per section type
 * (fields, variants, style options) and a Blade view "content.sections.{key}".
 */
abstract class SectionType
{
    private ?FieldSet $fieldSet = null;

    /**
     * @return list<Field>
     */
    abstract public function fields(): array;

    /**
     * Stable identifier stored in page documents. Never change it once content exists.
     */
    public function key(): string
    {
        return Str::kebab(Str::beforeLast(class_basename($this), 'Section'));
    }

    public function label(): string
    {
        return Str::headline($this->key());
    }

    public function description(): ?string
    {
        return null;
    }

    /**
     * Name of an icon shown in the admin's section library.
     */
    public function icon(): string
    {
        return 'square';
    }

    /**
     * Bump when the fields change in a way old content can't follow, and handle it in upgrade().
     */
    public function version(): int
    {
        return 1;
    }

    /**
     * Bring data saved by an older version up to the current one.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function upgrade(array $data, int $fromVersion): array
    {
        return $data;
    }

    /**
     * Layouts the admin can switch between: key => label. The first one is the default.
     *
     * @return array<string, string>
     */
    public function variants(): array
    {
        return [];
    }

    /**
     * Style options the admin can pick: option => [value => label]. Values map to CSS classes
     * through config('content.style_classes'), so the design stays in code.
     *
     * @return array<string, array<string, string>>
     */
    public function styles(): array
    {
        return [];
    }

    /**
     * Extra data for the view, e.g. records loaded from the database.
     *
     * @param  array<string, mixed>  $data  resolved field values
     * @return array<string, mixed>
     */
    public function with(array $data, ?ResolvedPage $page = null): array
    {
        return [];
    }

    public function view(): string
    {
        return config('content.section_views', 'content.sections').'.'.$this->key();
    }

    public function fieldSet(): FieldSet
    {
        return $this->fieldSet ??= new FieldSet($this->fields());
    }

    public function defaultVariant(): ?string
    {
        return array_key_first($this->variants());
    }

    /**
     * @return array<string, string>
     */
    public function defaultStyle(): array
    {
        return array_map(fn (array $values): string => (string) array_key_first($values), $this->styles());
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchema(): array
    {
        return [
            'key' => $this->key(),
            'label' => $this->label(),
            'description' => $this->description(),
            'icon' => $this->icon(),
            'version' => $this->version(),
            'variants' => $this->variants(),
            'styles' => $this->styles(),
            'fields' => $this->fieldSet()->toSchema(),
        ];
    }
}
