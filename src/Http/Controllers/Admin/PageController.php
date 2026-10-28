<?php

namespace PiShift\Content\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PiShift\Content\ContentManager;
use PiShift\Content\Document\PageDocument;
use PiShift\Content\Models\Page;

class PageController
{
    public function __construct(protected ContentManager $content) {}

    public function index(): Response
    {
        return Inertia::render('Pages/Index', [
            'pages' => Page::query()->orderBy('position')->orderBy('id')->get()->map(fn (Page $page): array => [
                'id' => $page->id,
                'key' => $page->key,
                'title' => $page->title ?? [],
                'path' => $page->path(),
                'status' => $page->isPublished() ? 'published' : 'draft',
                'hasChanges' => $page->hasUnpublishedChanges(),
                'updatedAt' => $page->updated_at?->toIso8601String(),
                'publishedAt' => $page->published_at?->toIso8601String(),
                'editUrl' => route('content.admin.pages.edit', $page),
            ]),
            'templates' => $this->templateOptions(),
            'createUrl' => route('content.admin.pages.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $defaultLocale = $this->content->localizer()->defaultLocale();

        $validated = $request->validate([
            'title' => ['required', 'array'],
            "title.{$defaultLocale}" => ['required', 'string', 'max:255'],
            'title.*' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9\-\/]+$/'],
            'template' => ['required', Rule::in(array_keys(config('content.templates', [])))],
        ]);

        $slug = trim($validated['slug'], '/');
        $this->ensureSlugIsFree($slug);

        $page = Page::create([
            'key' => $this->uniqueKey(Str::slug(str_replace('/', '-', $slug))),
            'title' => $validated['title'],
            'slug' => array_fill_keys(config('content.locales'), $slug),
            'template' => $validated['template'],
            'draft' => (new PageDocument)->toArray(),
            'position' => (int) Page::query()->max('position') + 1,
        ]);

        return redirect()->route('content.admin.pages.edit', $page)->with('content.success', __('content::admin.page_created'));
    }

    public function edit(Request $request, Page $page): Response
    {
        $document = PageDocument::fromArray($page->draft ?? $page->published)->normalize($this->content->sections());

        return Inertia::render('Pages/Edit', [
            'page' => [
                'id' => $page->id,
                'key' => $page->key,
                'title' => (object) ($page->title ?? []),
                'slug' => $page->slug[$this->content->localizer()->defaultLocale()] ?? collect($page->slug ?? [])->first() ?? '',
                'template' => $page->template,
                'seo' => (object) ($page->seo ?? []),
                'status' => $page->isPublished() ? 'published' : 'draft',
                'hasChanges' => $page->hasUnpublishedChanges(),
                'publishedAt' => $page->published_at?->toIso8601String(),
                'protected' => $this->isProtected($page),
            ],
            'document' => $document->toArray(),
            'sectionTypes' => $this->content->sections()->toSchema(),
            'templates' => $this->templateOptions(),
            'can' => ['publish' => $request->user()->can('content.publish')],
            'urls' => [
                'index' => route('content.admin.pages.index'),
                'update' => route('content.admin.pages.update', $page),
                'destroy' => route('content.admin.pages.destroy', $page),
                'preview' => $this->content->previewUrl($page),
                'view' => $page->path() !== null ? url($page->path()) : null,
            ],
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $defaultLocale = $this->content->localizer()->defaultLocale();

        $validated = $request->validate([
            'title' => ['required', 'array'],
            "title.{$defaultLocale}" => ['required', 'string', 'max:255'],
            'title.*' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9\-\/]*$/'],
            'template' => ['required', Rule::in(array_keys(config('content.templates', [])))],
            'seo' => ['nullable', 'array'],
            'seo.title' => ['nullable', 'array'],
            'seo.title.*' => ['nullable', 'string', 'max:255'],
            'seo.description' => ['nullable', 'array'],
            'seo.description.*' => ['nullable', 'string', 'max:500'],
            'document' => ['required', 'array'],
            'publish' => ['nullable', 'boolean'],
        ]);

        $slug = trim((string) ($validated['slug'] ?? ''), '/');

        if (! $this->isProtected($page)) {
            $this->ensureSlugIsFree($slug, $page);
            $page->slug = array_fill_keys(config('content.locales'), $slug);
        }

        $page->title = $validated['title'];
        $page->template = $validated['template'];
        $page->seo = $validated['seo'] ?? null;

        $this->content->saveDraft($page, $validated['document']);

        if ($request->boolean('publish')) {
            abort_unless($request->user()->can('content.publish'), 403);

            $page->publish($request->user()->getAuthIdentifier());

            return back()->with('content.success', __('content::admin.page_published'));
        }

        return back()->with('content.success', __('content::admin.draft_saved'));
    }

    public function destroy(Page $page): RedirectResponse
    {
        abort_if($this->isProtected($page), 403);

        $page->revisions()->delete();
        $page->delete();

        return redirect()->route('content.admin.pages.index')->with('content.success', __('content::admin.page_deleted'));
    }

    protected function isProtected(Page $page): bool
    {
        return in_array($page->key, config('content.protected_pages', []), true);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    protected function templateOptions(): array
    {
        return collect(config('content.templates', []))
            ->map(fn (array $template, string $key): array => ['value' => $key, 'label' => __($template['label'] ?? $key)])
            ->values()
            ->all();
    }

    protected function ensureSlugIsFree(string $slug, ?Page $except = null): void
    {
        $taken = Page::query()
            ->when($except, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->where(function ($query) use ($slug): void {
                foreach (config('content.locales', []) as $locale) {
                    $query->orWhere("slug->{$locale}", $slug);
                }
            })
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['slug' => __('content::admin.slug_taken')]);
        }
    }

    protected function uniqueKey(string $base): string
    {
        $base = $base === '' ? 'page' : $base;
        $key = $base;
        $suffix = 2;

        while (Page::query()->where('key', $key)->exists()) {
            $key = "{$base}-{$suffix}";
            $suffix++;
        }

        return $key;
    }
}
