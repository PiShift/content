<?php

namespace PiShift\Content\Collections\Drivers;

use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PiShift\Content\Collections\CollectionType;
use PiShift\Content\Fields\Field;

/**
 * Stores a collection in an existing Eloquent model: each field maps to the attribute of the
 * same name (translatable fields use spatie/laravel-translatable when the model has it).
 * Fields with no attribute (or a different shape) can be mapped with fillUsing/readUsing.
 */
class EloquentDriver implements CollectionDriver
{
    protected ?Closure $fillUsing = null;

    protected ?Closure $readUsing = null;

    protected ?Closure $queryUsing = null;

    /**
     * @param  class-string<Model>  $model
     */
    public function __construct(protected string $model) {}

    /**
     * Customize how validated data is written: fn (Model $record, array $data): void.
     * The default filling of fields named like attributes happens first.
     */
    public function fillUsing(Closure $callback): static
    {
        $this->fillUsing = $callback;

        return $this;
    }

    /**
     * Customize the form data read from a record: fn (Model $record, array $data): array.
     */
    public function readUsing(Closure $callback): static
    {
        $this->readUsing = $callback;

        return $this;
    }

    /**
     * Scope the list query: fn (Builder $query): void.
     */
    public function queryUsing(Closure $callback): static
    {
        $this->queryUsing = $callback;

        return $this;
    }

    public function paginate(CollectionType $collection, string $search, array $filters): LengthAwarePaginator
    {
        [$column, $direction] = $collection->defaultSort();

        return $this->model::query()
            ->when($this->queryUsing, fn (Builder $query) => ($this->queryUsing)($query))
            ->when($search !== '' && $collection->searchable() !== [], function (Builder $query) use ($collection, $search): void {
                $query->where(function (Builder $query) use ($collection, $search): void {
                    foreach ($collection->searchable() as $attribute) {
                        $query->orWhere($attribute, 'like', "%{$search}%");
                    }
                });
            })
            ->where(function (Builder $query) use ($collection, $filters): void {
                foreach ($filters as $field => $value) {
                    if (array_key_exists($field, $collection->filters()) && $value !== '') {
                        $query->where($field, $value);
                    }
                }
            })
            ->orderBy($column, $direction)
            ->paginate($collection->perPage())
            ->withQueryString();
    }

    public function find(CollectionType $collection, mixed $id): ?object
    {
        return $this->model::query()->find($id);
    }

    public function data(CollectionType $collection, object $record): array
    {
        /** @var Model $record */
        $data = [];

        foreach ($collection->fieldSet()->fields() as $field) {
            $data[$field->getName()] = $this->read($record, $field);
        }

        return $this->readUsing ? ($this->readUsing)($record, $data) : $data;
    }

    public function save(CollectionType $collection, ?object $record, array $data): object
    {
        /** @var Model $record */
        $record ??= new $this->model;

        foreach ($collection->fieldSet()->fields() as $field) {
            $name = $field->getName();

            if ($field->isReadonly() || ! array_key_exists($name, $data) || ! $this->hasAttribute($record, $name)) {
                continue;
            }

            $this->write($record, $field, $data[$name]);
        }

        // Values the admin derived from fields (e.g. an upload's original file name).
        $fieldNames = array_map(fn (Field $field): string => $field->getName(), $collection->fieldSet()->fields());

        foreach (array_diff_key($data, array_flip($fieldNames)) as $attribute => $value) {
            if ($record->isFillable($attribute)) {
                $record->setAttribute($attribute, $value);
            }
        }

        if ($this->fillUsing) {
            ($this->fillUsing)($record, $data);
        }

        $record->save();

        return $record;
    }

    public function delete(CollectionType $collection, object $record): void
    {
        /** @var Model $record */
        $record->delete();
    }

    public function id(object $record): mixed
    {
        /** @var Model $record */
        return $record->getKey();
    }

    protected function read(Model $record, Field $field): mixed
    {
        $name = $field->getName();

        if ($field->isTranslatable() && $this->isTranslatable($record, $name)) {
            return $record->getTranslations($name);
        }

        $value = $record->getAttribute($name);

        if ($value instanceof DateTimeInterface) {
            return $value->format($field->type() === 'datetime' ? 'Y-m-d\TH:i' : 'Y-m-d');
        }

        return $value;
    }

    protected function write(Model $record, Field $field, mixed $value): void
    {
        $name = $field->getName();

        if ($field->isTranslatable() && $this->isTranslatable($record, $name)) {
            $record->setTranslations($name, array_filter((array) $value, fn (mixed $text): bool => filled($text)));

            return;
        }

        $record->setAttribute($name, $value);
    }

    protected function isTranslatable(Model $record, string $attribute): bool
    {
        return method_exists($record, 'isTranslatableAttribute') && $record->isTranslatableAttribute($attribute);
    }

    protected function hasAttribute(Model $record, string $attribute): bool
    {
        return $record->isFillable($attribute) || $this->isTranslatable($record, $attribute);
    }
}
