<?php

namespace PiShift\Content\Fields;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Select extends Field
{
    /** @var array<string, string> */
    protected array $options = [];

    /** fn (string $search, int $page, int $perPage): array{options: array<string, string>, more: bool} */
    protected ?Closure $search = null;

    /** fn (list<string> $values): array<string, string> */
    protected ?Closure $labels = null;

    protected int $perPage = 20;

    /**
     * @param  array<string, string>  $options  value => label
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Options too many to list (hundreds of students…): the admin searches them on the server
     * and loads them page by page.
     *
     * @param  Closure(string, int, int): array{options: array<string, string>, more: bool}  $search
     * @param  Closure(list<string>): array<string, string>  $labels  labels of the given values
     */
    public function remote(Closure $search, Closure $labels, int $perPage = 20): static
    {
        $this->search = $search;
        $this->labels = $labels;
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * A remote select over a model: the value is the key, the label a column (or a callback),
     * searched in the given columns.
     *
     * @param  class-string<Model>  $model
     * @param  list<string>  $searchIn  columns searched (defaults to the label column)
     * @param  (Closure(Model): string)|null  $label
     * @param  (Closure(Builder): mixed)|null  $query  extra constraints and ordering
     */
    public function fromModel(string $model, string $labelColumn, array $searchIn = [], ?Closure $label = null, ?Closure $query = null, int $perPage = 20): static
    {
        $searchIn = $searchIn ?: [$labelColumn];
        $label ??= fn (Model $record): string => (string) $record->getAttribute($labelColumn);
        $base = function () use ($model, $labelColumn, $query): Builder {
            $builder = $model::query();

            return $query ? tap($builder, $query) : $builder->orderBy($labelColumn);
        };
        $map = fn ($records): array => $records->mapWithKeys(fn (Model $record): array => [(string) $record->getKey() => $label($record)])->all();

        return $this->remote(
            function (string $search, int $page, int $perPage) use ($base, $searchIn, $map): array {
                $records = $base()
                    ->when($search !== '', fn (Builder $builder) => $builder->where(function (Builder $where) use ($searchIn, $search): void {
                        foreach ($searchIn as $column) {
                            $where->orWhere($column, 'like', '%'.addcslashes($search, '%_\\').'%');
                        }
                    }))
                    ->skip(($page - 1) * $perPage)
                    ->take($perPage + 1)
                    ->get();

                return ['options' => $map($records->take($perPage)), 'more' => $records->count() > $perPage];
            },
            fn (array $values): array => $values === [] ? [] : $map($model::query()->whereKey($values)->get()),
            $perPage,
        );
    }

    public function isRemote(): bool
    {
        return $this->search !== null;
    }

    /**
     * One page of options matching the search.
     *
     * @return array{options: list<array{value: string, label: string}>, more: bool}
     */
    public function search(string $search, int $page = 1): array
    {
        if (! $this->isRemote()) {
            $options = array_filter($this->options, fn (string $label): bool => $search === '' || mb_stripos($label, $search) !== false);

            return ['options' => $this->pairs($options), 'more' => false];
        }

        $result = ($this->search)(trim($search), max(1, $page), $this->perPage);

        return ['options' => $this->pairs($result['options']), 'more' => (bool) $result['more']];
    }

    /**
     * Labels of the given values (unknown values are left out).
     *
     * @param  list<mixed>  $values
     * @return array<string, string>
     */
    public function labelsFor(array $values): array
    {
        $values = array_values(array_unique(array_map('strval', array_filter($values, fn ($value): bool => $value !== null && $value !== ''))));

        if (! $this->isRemote()) {
            return array_intersect_key($this->options, array_flip($values));
        }

        return $values === [] ? [] : ($this->labels)($values);
    }

    public function type(): string
    {
        return 'select';
    }

    /**
     * One of the options. Not "string": a value read back from an integer column (e.g. a parent
     * id) is sent back as a number, and the in rule already compares as text.
     */
    protected function valueRules(): array
    {
        if ($this->isRemote()) {
            return [function (string $attribute, mixed $value, Closure $fail): void {
                if ($this->labelsFor([$value]) === []) {
                    $fail(__('validation.in', ['attribute' => $this->getLabel()]));
                }
            }];
        }

        return [Rule::in(array_map('strval', array_keys($this->options)))];
    }

    protected function extraSchema(): array
    {
        return $this->isRemote() ? ['options' => [], 'remote' => true] : ['options' => $this->options];
    }

    /**
     * @param  array<string, string>  $options
     * @return list<array{value: string, label: string}>
     */
    private function pairs(array $options): array
    {
        return array_map(fn ($value, string $label): array => ['value' => (string) $value, 'label' => $label], array_keys($options), $options);
    }
}
