<?php

namespace PiShift\Content\Collections\Drivers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use PiShift\Content\Collections\CollectionType;
use PiShift\Content\Models\Entry;

/**
 * Stores a collection in the generic content_entries table (one JSON document per record),
 * for collections that don't need their own table.
 */
class EntriesDriver implements CollectionDriver
{
    public function paginate(CollectionType $collection, string $search, array $filters): LengthAwarePaginator
    {
        [$column, $direction] = $collection->defaultSort();

        return Entry::query()
            ->where('collection', $collection->key())
            ->when($search !== '' && $collection->searchable() !== [], function (Builder $query) use ($collection, $search): void {
                $query->where(function (Builder $query) use ($collection, $search): void {
                    foreach ($collection->searchable() as $field) {
                        $query->orWhere("data->{$field}", 'like', "%{$search}%");
                    }
                });
            })
            ->where(function (Builder $query) use ($collection, $filters): void {
                foreach ($filters as $field => $value) {
                    if (array_key_exists($field, $collection->filters()) && $value !== '') {
                        $query->where("data->{$field}", $value);
                    }
                }
            })
            ->orderBy(in_array($column, ['id', 'position', 'created_at', 'updated_at'], true) ? $column : "data->{$column}", $direction)
            ->paginate($collection->perPage())
            ->withQueryString();
    }

    public function find(CollectionType $collection, mixed $id): ?object
    {
        return Entry::query()->where('collection', $collection->key())->find($id);
    }

    public function data(CollectionType $collection, object $record): array
    {
        /** @var Entry $record */
        return $collection->fieldSet()->normalize($record->data ?? []);
    }

    public function save(CollectionType $collection, ?object $record, array $data): object
    {
        /** @var Entry|null $record */
        $record ??= new Entry(['collection' => $collection->key()]);
        $record->data = [...($record->data ?? []), ...$data];
        $record->save();

        return $record;
    }

    public function delete(CollectionType $collection, object $record): void
    {
        /** @var Entry $record */
        $record->delete();
    }

    public function id(object $record): mixed
    {
        /** @var Entry $record */
        return $record->getKey();
    }
}
