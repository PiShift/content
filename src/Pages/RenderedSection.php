<?php

namespace PiShift\Content\Pages;

use Illuminate\Support\Facades\View;
use PiShift\Content\Sections\SectionType;

/**
 * One section of a resolved page, with everything its view needs.
 */
class RenderedSection
{
    /**
     * @param  array<string, string>  $style
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly SectionType $type,
        public readonly string $id,
        public readonly ?string $variant,
        public readonly array $style,
        public readonly array $data,
        public readonly ?ResolvedPage $page = null,
    ) {}

    /**
     * CSS classes for the chosen style options, from config('content.style_classes').
     */
    public function classes(): string
    {
        $classes = config('content.style_classes', []);

        return collect($this->style)
            ->map(fn (?string $value, string $option): ?string => $value === null ? null : ($classes[$option][$value] ?? null))
            ->filter()
            ->implode(' ');
    }

    /**
     * Marks an element as showing a field, so clicking it in the admin preview opens that
     * field: <h2{!! $section->attr('title') !!}>. Outputs nothing outside the preview.
     */
    public function attr(string $field): string
    {
        return $this->page?->isPreview ? ' data-content-field="'.e($field).'"' : '';
    }

    public function viewExists(): bool
    {
        return View::exists($this->type->view());
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(): array
    {
        return [
            ...$this->type->with($this->data, $this->page),
            'data' => $this->data,
            'section' => $this,
            'variant' => $this->variant,
            'classes' => $this->classes(),
        ];
    }

    public function render(): string
    {
        return View::make($this->type->view(), $this->viewData())->render();
    }
}
