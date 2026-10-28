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
            ...$this->extraSchema(),
        ];
    }

    /**
     * Rules for this field at a dotted path, e.g. "sections.0.data.title".
     *
     * @return array<string, list<mixed>>
     */
    public function rules(string $path, Localizer $localizer): array
    {
        if (! $this->translatable) {
            return [$path => [$this->required ? 'required' : 'nullable', ...$this->valueRules()]];
        }

        return [
            $path => [$this->required ? 'required' : 'nullable', 'array'],
            "{$path}.{$localizer->defaultLocale()}" => [$this->required ? 'required' : 'nullable', ...$this->valueRules()],
            "{$path}.*" => ['nullable', ...$this->valueRules()],
        ];
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
