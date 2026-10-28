<?php

namespace PiShift\Content;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use PiShift\Content\Document\PageDocument;
use PiShift\Content\Globals\GlobalRegistry;
use PiShift\Content\Models\GlobalSet;
use PiShift\Content\Models\Page;
use PiShift\Content\Pages\ResolvedPage;
use PiShift\Content\Sections\SectionRegistry;
use PiShift\Content\Support\GlobalCache;
use PiShift\Content\Support\Localizer;

class ContentManager
{
    public const PREVIEW_ATTRIBUTE = 'content.preview';

    /** @var array<string, array<string, mixed>> */
    protected array $resolvedGlobals = [];

    public function __construct(
        protected SectionRegistry $sections,
        protected GlobalRegistry $globals,
    ) {}

    public function sections(): SectionRegistry
    {
        return $this->sections;
    }

    public function globals(): GlobalRegistry
    {
        return $this->globals;
    }

    public function localizer(?string $locale = null): Localizer
    {
        return Localizer::fromConfig($locale);
    }

    /**
     * True while rendering a signed preview: drafts are shown instead of published content.
     */
    public function previewing(): bool
    {
        return (bool) request()->attributes->get(self::PREVIEW_ATTRIBUTE, false);
    }

    /**
     * The published page with this key (or its draft in preview), or null.
     */
    public function page(string $key): ?ResolvedPage
    {
        $page = Page::query()->where('key', $key)->first();

        return $page ? $this->resolve($page) : null;
    }

    /**
     * The page whose slug, in any locale, is this path ("" is the home page).
     */
    public function pageBySlug(string $slug): ?ResolvedPage
    {
        $slug = trim($slug, '/');

        $page = Page::query()
            ->where(function ($query) use ($slug): void {
                foreach (config('content.locales', []) as $locale) {
                    $query->orWhere("slug->{$locale}", $slug);
                }
            })
            ->first();

        return $page ? $this->resolve($page) : null;
    }

    public function resolve(Page $page, ?bool $draft = null): ?ResolvedPage
    {
        $draft ??= $this->previewing();

        if (! $draft && ! $page->isPublished()) {
            return null;
        }

        $document = ($draft ? $page->draftDocument() : $page->publishedDocument())->normalize($this->sections);

        return new ResolvedPage($page, $document, $this->sections, $this->localizer(), $draft);
    }

    /**
     * A page rendered from an unsaved document (the editor's live preview). Not validated:
     * fields resolve whatever they are given.
     *
     * @param  array<string, mixed>  $document
     */
    public function resolveDocument(Page $page, array $document): ResolvedPage
    {
        request()->attributes->set(self::PREVIEW_ATTRIBUTE, true);

        $normalized = PageDocument::fromArray($document)->normalize($this->sections);

        return new ResolvedPage($page, $normalized, $this->sections, $this->localizer(), true);
    }

    /**
     * Normalize, validate and store a page draft.
     *
     * @param  array<string, mixed>  $document
     */
    public function saveDraft(Page $page, array $document): Page
    {
        $normalized = PageDocument::fromArray($document)
            ->normalize($this->sections)
            ->validate($this->sections, $this->localizer());

        $page->draft = $normalized->toArray();
        $page->save();

        return $page;
    }

    public function previewUrl(Page $page, ?string $locale = null): string
    {
        return URL::temporarySignedRoute(
            'content.preview',
            now()->addMinutes((int) config('content.routes.preview_minutes', 120)),
            array_filter(['page' => $page->getKey(), 'locale' => $locale]),
        );
    }

    /**
     * Resolved data of a global for the current locale, or one value of it by dotted path.
     */
    public function global(string $key, ?string $path = null, mixed $default = null): mixed
    {
        $data = $this->resolvedGlobal($key);

        if ($path === null) {
            return $data;
        }

        $value = Arr::get($data, $path);

        return filled($value) ? $value : $default;
    }

    /**
     * Normalize, validate and store a global's draft data.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveGlobalDraft(string $key, array $data): GlobalSet
    {
        $type = $this->globals->get($key) ?? throw new InvalidArgumentException("Unknown global [{$key}].");

        $data = $type->fieldSet()->normalize($data);

        Validator::make(['data' => $data], $type->fieldSet()->rules('data', $this->localizer()))->validate();

        $global = GlobalSet::query()->firstOrNew(['key' => $key]);
        $global->draft = ['version' => $type->version(), 'data' => $data];
        $global->save();

        return $global;
    }

    public function forgetResolved(string $key): void
    {
        foreach (array_keys($this->resolvedGlobals) as $cacheKey) {
            if (str_starts_with($cacheKey, "{$key}|")) {
                unset($this->resolvedGlobals[$cacheKey]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolvedGlobal(string $key): array
    {
        $type = $this->globals->get($key);

        if (! $type) {
            return [];
        }

        $draft = $this->previewing();
        $cacheKey = $key.'|'.app()->getLocale().'|'.($draft ? 'draft' : 'published');

        if (isset($this->resolvedGlobals[$cacheKey])) {
            return $this->resolvedGlobals[$cacheKey];
        }

        $stored = $draft
            ? GlobalSet::query()->where('key', $key)->value('draft')
            : Cache::rememberForever(GlobalCache::key($key), fn () => GlobalSet::query()->where('key', $key)->first()?->published);

        $stored = is_string($stored) ? json_decode($stored, true) : $stored;
        $stored = is_array($stored) ? $stored : [];

        $data = is_array($stored['data'] ?? null) ? $stored['data'] : [];
        $savedVersion = (int) ($stored['version'] ?? 1);

        if ($savedVersion < $type->version()) {
            $data = $type->upgrade($data, $savedVersion);
        }

        return $this->resolvedGlobals[$cacheKey] = $type->fieldSet()->resolve($type->fieldSet()->normalize($data), $this->localizer());
    }
}
