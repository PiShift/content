<?php

namespace PiShift\Content\Collections;

use Illuminate\Support\Str;
use PiShift\Content\Collections\Drivers\CollectionDriver;
use PiShift\Content\Fields\Field;
use PiShift\Content\Fields\FieldSet;

/**
 * A list of records the admin manages (news, documents, team members…): fields for the
 * form, columns and filters for the list, and a driver that stores the records, either in
 * the generic content_entries table or in an existing Eloquent model.
 */
abstract class CollectionType
{
    private ?FieldSet $fieldSet = null;

    /**
     * @return list<Field>
     */
    abstract public function fields(): array;

    abstract public function driver(): CollectionDriver;

    public function key(): string
    {
        return Str::kebab(Str::beforeLast(class_basename($this), 'Collection'));
    }

    public function label(): string
    {
        return Str::headline($this->key());
    }

    public function singularLabel(): string
    {
        return Str::singular($this->label());
    }

    public function icon(): string
    {
        return 'list';
    }

    /**
     * Ability required to manage the collection.
     */
    public function ability(): string
    {
        return 'content.manage';
    }

    /**
     * Menu group in the admin; null puts it at the top level.
     */
    public function group(): ?string
    {
        return 'content::admin.collections_group';
    }

    public function sort(): int
    {
        return 50;
    }

    /**
     * Columns of the list: field name => label. Defaults to the first three fields.
     *
     * @return array<string, string>
     */
    public function columns(): array
    {
        return collect($this->fields())->take(3)->mapWithKeys(fn (Field $field): array => [$field->getName() => $field->getLabel()])->all();
    }

    /**
     * Filters of the list: field name => [value => label].
     *
     * @return array<string, array<string, string>>
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * Columns searched by the list's search box.
     *
     * @return list<string>
     */
    public function searchable(): array
    {
        return [];
    }

    /**
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    public function defaultSort(): array
    {
        return ['id', 'desc'];
    }

    public function perPage(): int
    {
        return 20;
    }

    /**
     * Field shown as each record's title in the list and the form heading.
     */
    public function titleField(): string
    {
        return $this->fields()[0]->getName();
    }

    /**
     * Validation rules added to the fields' own, keyed by field name (e.g. a unique slug).
     *
     * @return array<string, list<mixed>>
     */
    public function rules(mixed $recordId = null): array
    {
        return [];
    }

    public function canCreate(): bool
    {
        return true;
    }

    public function canDelete(object $record): bool
    {
        return true;
    }

    /**
     * Buttons on a record's page.
     *
     * @return list<Action>
     */
    public function actions(): array
    {
        return [];
    }

    public function action(string $key): ?Action
    {
        return collect($this->actions())->first(fn (Action $action): bool => $action->key() === $key);
    }

    /**
     * Columns shown as coloured badges: field name => [value => tone], tones being
     * neutral, info, success, warning or danger.
     *
     * @return array<string, array<string, string>>
     */
    public function badges(): array
    {
        return [];
    }

    /**
     * Count shown next to the menu entry (e.g. requests waiting for an answer).
     */
    public function navigationBadge(): ?int
    {
        return null;
    }

    /**
     * Called after a record is saved from the admin (e.g. dispatch a job).
     */
    public function saved(object $record, bool $created): void {}

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
            'singularLabel' => $this->singularLabel(),
            'icon' => $this->icon(),
            'titleField' => $this->titleField(),
            'fields' => $this->fieldSet()->toSchema(),
            'columns' => $this->columns(),
            'filters' => $this->filters(),
            'searchable' => $this->searchable() !== [],
            'badges' => (object) $this->badges(),
            'canCreate' => $this->canCreate(),
        ];
    }
}
