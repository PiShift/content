<?php

namespace PiShift\Content\Fields;

use Illuminate\Support\Str;
use PiShift\Content\Support\Localizer;

/**
 * A list of items (cards, links, steps…), each with its own fields. Every item keeps a
 * stable "_id" so the admin can reorder it and the preview can point at it.
 */
class Repeater extends Field
{
    /** @var list<Field> */
    protected array $fields = [];

    protected ?int $min = null;

    protected ?int $max = null;

    protected ?string $itemLabel = null;

    protected mixed $default = [];

    /**
     * @param  list<Field>  $fields
     */
    public function fields(array $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    public function min(int $min): static
    {
        $this->min = $min;

        return $this;
    }

    public function max(int $max): static
    {
        $this->max = $max;

        return $this;
    }

    /**
     * Name of the sub-field shown as each item's title in the admin.
     */
    public function itemLabel(string $field): static
    {
        $this->itemLabel = $field;

        return $this;
    }

    /**
     * @return list<Field>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function type(): string
    {
        return 'repeater';
    }

    protected function valueRules(): array
    {
        return ['array'];
    }

    public function rules(string $path, Localizer $localizer): array
    {
        $rules = [
            $path => array_values(array_filter([
                $this->required || $this->min ? 'required' : 'nullable',
                'array',
                $this->min !== null ? "min:{$this->min}" : null,
                $this->max !== null ? "max:{$this->max}" : null,
            ])),
            "{$path}.*" => ['array'],
            "{$path}.*._id" => ['nullable', 'string', 'max:64'],
        ];

        foreach ($this->fields as $field) {
            $rules = [...$rules, ...$field->rules("{$path}.*.{$field->getName()}", $localizer)];
        }

        return $rules;
    }

    public function normalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(function (mixed $item): array {
            $item = is_array($item) ? $item : [];
            $item['_id'] = filled($item['_id'] ?? null) ? (string) $item['_id'] : (string) Str::uuid();

            foreach ($this->fields as $field) {
                $item[$field->getName()] = $field->normalize($item[$field->getName()] ?? null);
            }

            return $item;
        }, $value));
    }

    public function resolve(mixed $value, Localizer $localizer): mixed
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(function (array $item) use ($localizer): array {
            $resolved = ['_id' => $item['_id'] ?? null];

            foreach ($this->fields as $field) {
                $resolved[$field->getName()] = $field->resolve($item[$field->getName()] ?? null, $localizer);
            }

            return $resolved;
        }, array_filter($value, 'is_array')));
    }

    protected function extraSchema(): array
    {
        return [
            'fields' => array_map(fn (Field $field): array => $field->toSchema(), $this->fields),
            'min' => $this->min,
            'max' => $this->max,
            'itemLabel' => $this->itemLabel,
        ];
    }
}
