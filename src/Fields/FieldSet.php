<?php

namespace PiShift\Content\Fields;

use PiShift\Content\Support\Localizer;

/**
 * The fields of a section, global or collection, handled together.
 */
class FieldSet
{
    /**
     * @param  list<Field>  $fields
     */
    public function __construct(protected array $fields) {}

    /**
     * @return list<Field>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toSchema(): array
    {
        return array_map(fn (Field $field): array => $field->toSchema(), $this->fields);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(string $prefix, Localizer $localizer): array
    {
        $rules = [];

        foreach ($this->fields as $field) {
            $rules = [...$rules, ...$field->validationRules(ltrim("{$prefix}.{$field->getName()}", '.'), $localizer)];
        }

        return $rules;
    }

    /**
     * Normalize known fields; keys no field knows about are kept untouched.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function normalize(array $data): array
    {
        foreach ($this->fields as $field) {
            $data[$field->getName()] = $field->normalize($data[$field->getName()] ?? null);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function resolve(array $data, Localizer $localizer): array
    {
        $resolved = [];

        foreach ($this->fields as $field) {
            $resolved[$field->getName()] = $field->resolve($data[$field->getName()] ?? null, $localizer);
        }

        return $resolved;
    }
}
