<?php

namespace PiShift\Content\Fields;

use Illuminate\Support\Str;
use PiShift\Content\Support\Localizer;

/**
 * A piece of content in a section, global or collection. Fields describe themselves to the
 * admin (toSchema), validate submitted data (rules), and turn stored values into what views
 * receive (resolve).
 */
abstract class Field
{
    protected ?string $label = null;

    protected bool $translatable = false;

    protected bool $required = false;

    protected ?string $help = null;

    protected mixed $default = null;

    /** @var array{field: string, values: list<string>}|null */
    protected ?array $visibleWhen = null;

    /** @var list<mixed> */
    protected array $extraRules = [];

    protected bool $readonly = false;

    final public function __construct(protected string $name) {}

    public static function make(string $name): static
    {
        return new static($name);
    }

    public static function text(string $name): Text
    {
        return Text::make($name);
    }

    public static function textarea(string $name): Textarea
    {
        return Textarea::make($name);
    }

    public static function richText(string $name): RichText
    {
        return RichText::make($name);
    }

    public static function number(string $name): Number
    {
        return Number::make($name);
    }

    public static function toggle(string $name): Toggle
    {
        return Toggle::make($name);
    }

    public static function select(string $name): Select
    {
        return Select::make($name);
    }

    public static function icon(string $name): Icon
    {
        return Icon::make($name);
    }

    public static function date(string $name): Date
    {
        return Date::make($name);
    }

    public static function dateTime(string $name): DateTime
    {
        return DateTime::make($name);
    }

    public static function link(string $name): Link
    {
        return Link::make($name);
    }

    public static function image(string $name): Image
    {
        return Image::make($name);
    }

    public static function file(string $name): File
    {
        return File::make($name);
    }

    public static function color(string $name): Color
    {
        return Color::make($name);
    }

    public static function upload(string $name): Upload
    {
        return Upload::make($name);
    }

    /**
     * @param  list<Field>  $fields
     */
    public static function repeater(string $name, array $fields = []): Repeater
    {
        return Repeater::make($name)->fields($fields);
    }

    /**
     * The field type sent to the admin, which picks the matching input.
     */
    abstract public function type(): string;

    /**
     * Validation rules for one (non-translated) value.
     *
     * @return list<mixed>
     */
    abstract protected function valueRules(): array;

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function translatable(bool $translatable = true): static
    {
        $this->translatable = $translatable;

        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function help(string $help): static
    {
        $this->help = $help;

        return $this;
    }

    public function default(mixed $default): static
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Show the field only when a sibling field has one of these values (e.g. a deadline only
     * for tenders). Hidden fields are not required.
     *
     * @param  string|list<string>  $values
     */
    public function visibleWhen(string $field, string|array $values): static
    {
        $this->visibleWhen = ['field' => $field, 'values' => array_values((array) $values)];

        return $this;
    }

    /**
     * Extra validation rules for the value, e.g. ['regex:/^[a-z0-9-]+$/'].
     *
     * @param  list<mixed>  $rules
     */
    public function rules(array $rules): static
    {
        $this->extraRules = [...$this->extraRules, ...$rules];

        return $this;
    }

    /**
     * Shown in the form but never written (e.g. a status set by a background job).
     */
    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;

        return $this;
    }

    public function isReadonly(): bool
    {
        return $this->readonly;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label ?? Str::headline($this->name);
    }

    public function isTranslatable(): bool
    {
        return $this->translatable;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchema(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type(),
            'label' => $this->getLabel(),
            'translatable' => $this->translatable,
            'required' => $this->required,
            'help' => $this->help,
            'default' => $this->default,
            'visibleWhen' => $this->visibleWhen,
            'readonly' => $this->readonly,
            ...$this->extraSchema(),
        ];
    }

    /**
     * Rules for this field at a dotted path, e.g. "sections.0.data.title".
     *
     * @return array<string, list<mixed>>
     */
    public function validationRules(string $path, Localizer $localizer): array
    {
        $values = [...$this->valueRules(), ...$this->extraRules];

        if (! $this->translatable) {
            return [$path => [...$this->presenceRules($path), ...$values]];
        }

        return [
            $path => [...$this->presenceRules($path), 'array'],
            "{$path}.{$localizer->defaultLocale()}" => [...$this->presenceRules($path), ...$values],
            "{$path}.*" => ['nullable', ...$values],
        ];
    }

    /**
     * ["required"], ["nullable"], or a "required_if" on the sibling field that controls the
     * field's visibility (nullable while hidden).
     *
     * @return list<string>
     */
    protected function presenceRules(string $path): array
    {
        if (! $this->required) {
            return ['nullable'];
        }

        if ($this->visibleWhen === null) {
            return ['required'];
        }

        $sibling = (str_contains($path, '.') ? Str::beforeLast($path, '.').'.' : '').$this->visibleWhen['field'];

        return ['required_if:'.$sibling.','.implode(',', $this->visibleWhen['values']), 'nullable'];
    }

    /**
     * Prepare a stored value (fill the default, give repeater items ids…).
     */
    public function normalize(mixed $value): mixed
    {
        return $value ?? $this->default;
    }

    /**
     * Turn a stored value into what the view receives for the current locale.
     */
    public function resolve(mixed $value, Localizer $localizer): mixed
    {
        if ($this->translatable) {
            $value = $localizer->pick($value);
        }

        return $this->resolveValue($value ?? ($this->translatable ? $localizer->pick($this->default) : $this->default), $localizer);
    }

    protected function resolveValue(mixed $value, Localizer $localizer): mixed
    {
        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    protected function extraSchema(): array
    {
        return [];
    }
}
