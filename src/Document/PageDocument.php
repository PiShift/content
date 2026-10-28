<?php

namespace PiShift\Content\Document;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PiShift\Content\Sections\SectionRegistry;
use PiShift\Content\Support\Localizer;

/**
 * The content of a page: an ordered list of sections.
 *
 * Stored as {"version": 1, "sections": [{"id", "type", "version", "variant", "style", "hidden", "data"}]}.
 * Sections whose type the registry doesn't know, and fields no type declares, are kept as they
 * are: an older or newer schema never loses content.
 */
class PageDocument
{
    public const VERSION = 1;

    /**
     * @param  list<array<string, mixed>>  $sections
     * @param  array<string, mixed>  $extra  top-level keys this version doesn't use
     */
    public function __construct(protected array $sections = [], protected array $extra = []) {}

    /**
     * @param  array<string, mixed>|null  $document
     */
    public static function fromArray(?array $document): self
    {
        $document ??= [];
        $sections = array_values(array_filter($document['sections'] ?? [], 'is_array'));

        unset($document['sections'], $document['version']);

        return new self($sections, $document);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function sections(): array
    {
        return $this->sections;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [...$this->extra, 'version' => self::VERSION, 'sections' => $this->sections];
    }

    /**
     * Give every section an id, upgrade sections saved by older type versions, fill defaults.
     */
    public function normalize(SectionRegistry $registry): self
    {
        $sections = array_map(function (array $section) use ($registry): array {
            $section['id'] = filled($section['id'] ?? null) ? (string) $section['id'] : (string) Str::uuid();
            $section['hidden'] = (bool) ($section['hidden'] ?? false);
            $section['data'] = is_array($section['data'] ?? null) ? $section['data'] : [];

            $type = $registry->get((string) ($section['type'] ?? ''));

            if (! $type) {
                return $section;
            }

            $savedVersion = (int) ($section['version'] ?? 1);

            if ($savedVersion < $type->version()) {
                $section['data'] = $type->upgrade($section['data'], $savedVersion);
            }

            $section['version'] = max($savedVersion, $type->version());
            $section['data'] = $type->fieldSet()->normalize($section['data']);

            $variants = $type->variants();
            if ($variants !== [] && ! array_key_exists((string) ($section['variant'] ?? ''), $variants)) {
                $section['variant'] = $type->defaultVariant();
            }

            $section['style'] = [...$type->defaultStyle(), ...array_intersect_key(
                is_array($section['style'] ?? null) ? $section['style'] : [],
                $type->styles(),
            )];

            return $section;
        }, $this->sections);

        return new self($sections, $this->extra);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(SectionRegistry $registry, Localizer $localizer): array
    {
        $rules = [
            'sections' => ['present', 'array'],
            'sections.*' => ['array'],
            'sections.*.id' => ['nullable', 'string', 'max:64'],
            'sections.*.type' => ['required', 'string', 'max:64'],
            'sections.*.hidden' => ['nullable', 'boolean'],
            'sections.*.data' => ['nullable', 'array'],
        ];

        foreach ($this->sections as $index => $section) {
            $type = $registry->get((string) ($section['type'] ?? ''));

            if (! $type) {
                continue;
            }

            if ($type->variants() !== []) {
                $rules["sections.{$index}.variant"] = ['nullable', Rule::in(array_keys($type->variants()))];
            }

            foreach ($type->styles() as $option => $values) {
                $rules["sections.{$index}.style.{$option}"] = ['nullable', Rule::in(array_keys($values))];
            }

            $rules = [...$rules, ...$type->fieldSet()->rules("sections.{$index}.data", $localizer)];
        }

        return $rules;
    }

    /**
     * Validate against the registered section types; throws a ValidationException.
     */
    public function validate(SectionRegistry $registry, Localizer $localizer): self
    {
        Validator::make($this->toArray(), $this->rules($registry, $localizer))->validate();

        return $this;
    }
}
