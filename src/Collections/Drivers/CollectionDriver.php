<?php

namespace PiShift\Content\Collections\Drivers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use PiShift\Content\Collections\CollectionType;

/**
 * Where a collection's records live.
 */
interface CollectionDriver
{
    /**
     * @param  array<string, string>  $filters
     */
    public function paginate(CollectionType $collection, string $search, array $filters): LengthAwarePaginator;

    public function find(CollectionType $collection, mixed $id): ?object;

    /**
     * Field values of a record, as the admin form edits them.
     *
     * @return array<string, mixed>
     */
    public function data(CollectionType $collection, object $record): array;

    /**
     * Create (record null) or update a record from validated field values.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(CollectionType $collection, ?object $record, array $data): object;

    public function delete(CollectionType $collection, object $record): void;

    public function id(object $record): mixed;
}
