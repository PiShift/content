<?php

namespace PiShift\Content\Pages;

use PiShift\Content\Document\PageDocument;
use PiShift\Content\Models\Page;
use PiShift\Content\Sections\SectionRegistry;
use PiShift\Content\Support\Localizer;

/**
 * A page ready to render: its document (published, or draft in preview) resolved for the
 * current locale.
 */
class ResolvedPage
{
    /** @var list<RenderedSection>|null */
    protected ?array $sections = null;

    public function __construct(
        public readonly Page $page,
        public readonly PageDocument $document,
        protected SectionRegistry $registry,
        protected Localizer $localizer,
        public readonly bool $isPreview = false,
    ) {}

    /**
     * Visible sections whose type is known, in order.
     *
     * @return list<RenderedSection>
     */
    public function sections(): array
    {
        return $this->sections ??= collect($this->document->sections())
            ->reject(fn (array $section): bool => (bool) ($section['hidden'] ?? false))
            ->map(function (array $section): ?RenderedSection {
                $type = $this->registry->get((string) ($section['type'] ?? ''));

                if (! $type) {
                    return null;
                }

                return new RenderedSection(
                    type: $type,
                    id: (string) $section['id'],
                    variant: $section['variant'] ?? $type->defaultVariant(),
                    style: $section['style'] ?? [],
                    data: $type->fieldSet()->resolve($section['data'] ?? [], $this->localizer),
                    page: $this,
                );
            })
            ->filter()
            ->values()
            ->all();
    }

    public function key(): string
    {
        return $this->page->key;
    }

    public function title(): ?string
    {
        return $this->localizer->pick($this->page->title);
    }

    public function seoTitle(): ?string
    {
        return $this->localizer->pick($this->page->seo['title'] ?? null) ?? $this->title();
    }

    public function seoDescription(): ?string
    {
        return $this->localizer->pick($this->page->seo['description'] ?? null);
    }

    public function templateView(): string
    {
        $templates = config('content.templates', []);

        return $templates[$this->page->template]['view'] ?? 'content::templates.default';
    }
}
