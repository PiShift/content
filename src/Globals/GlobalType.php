<?php

namespace PiShift\Content\Globals;

use Illuminate\Support\Str;
use PiShift\Content\Fields\Field;
use PiShift\Content\Fields\FieldSet;

/**
 * Content shared across pages (site identity, contact, menus, theme). Projects define one
 * class per global; the admin edits it with the same generated forms as sections.
 */
abstract class GlobalType
{
    private ?FieldSet $fieldSet = null;

    /**
     * @return list<Field>
     */
    abstract public function fields(): array;

    public function key(): string
    {
        return Str::kebab(class_basename($this));
    }

    public function label(): string
    {
        return Str::headline($this->key());
    }

    public function icon(): string
    {
        return 'settings';
    }

    public function version(): int
    {
        return 1;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function upgrade(array $data, int $fromVersion): array
    {
        return $data;
    }

    public function fieldSet(): FieldSet
    {
        return $this->fieldSet ??= new FieldSet($this->fields());
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchema(): array
    {
        return [
            'key' => $this->key(),
            'label' => $this->label(),
            'icon' => $this->icon(),
            'version' => $this->version(),
            'fields' => $this->fieldSet()->toSchema(),
        ];
    }
}
