<?php

namespace PiShift\Content\Fields;

use PiShift\Content\Support\Localizer;

/**
 * A link: translatable label, URL (absolute, or a site path like "/about") and target.
 *
 * Stored as ['label' => ['ar' => …, 'fr' => …], 'url' => '/about', 'new_tab' => false].
 */
class Link extends Field
{
    protected bool $withLabel = true;

    public function withoutLabel(): static
    {
        $this->withLabel = false;

        return $this;
    }

    public function type(): string
    {
        return 'link';
    }

    protected function valueRules(): array
    {
        return ['array'];
    }

    public function validationRules(string $path, Localizer $localizer): array
    {
        return [
            $path => ['nullable', 'array'],
            "{$path}.url" => [...$this->presenceRules($path), 'string', 'max:2048'],
            "{$path}.label" => ['nullable', 'array'],
            "{$path}.label.*" => ['nullable', 'string', 'max:255'],
            "{$path}.new_tab" => ['nullable', 'boolean'],
        ];
    }

    public function resolve(mixed $value, Localizer $localizer): mixed
    {
        if (! is_array($value) || blank($value['url'] ?? null)) {
            return null;
        }

        return [
            'url' => $value['url'],
            'label' => $localizer->pick($value['label'] ?? null),
            'new_tab' => (bool) ($value['new_tab'] ?? false),
        ];
    }

    protected function extraSchema(): array
    {
        return ['withLabel' => $this->withLabel, 'translatable' => true];
    }
}
