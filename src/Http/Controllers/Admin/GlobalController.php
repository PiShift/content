<?php

namespace PiShift\Content\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PiShift\Content\ContentManager;
use PiShift\Content\Globals\GlobalType;
use PiShift\Content\Models\GlobalSet;

/**
 * Editing of globals: site identity, contact, menus, theme… whatever the project defines.
 *
 * @internal Not part of the public API; may change in any release.
 */
class GlobalController
{
    public function __construct(protected ContentManager $content) {}

    public function index(): Response
    {
        $stored = GlobalSet::query()->get()->keyBy('key');

        return Inertia::render('Globals/Index', [
            'globals' => collect($this->content->globals()->all())->map(fn (GlobalType $type): array => [
                'key' => $type->key(),
                'label' => __($type->label()),
                'icon' => $type->icon(),
                'status' => $stored->get($type->key())?->published_at ? 'published' : 'draft',
                'hasChanges' => (bool) $stored->get($type->key())?->hasUnpublishedChanges(),
                'editUrl' => route('content.admin.globals.edit', $type->key()),
            ])->values(),
        ]);
    }

    public function edit(Request $request, string $key): Response
    {
        $type = $this->type($key);
        $global = GlobalSet::query()->where('key', $key)->first();
        $stored = $global?->draft ?? $global?->published ?? [];
        $data = is_array($stored['data'] ?? null) ? $stored['data'] : [];

        if ((int) ($stored['version'] ?? 1) < $type->version()) {
            $data = $type->upgrade($data, (int) ($stored['version'] ?? 1));
        }

        return Inertia::render('Globals/Edit', [
            'global' => [
                'key' => $type->key(),
                'label' => __($type->label()),
                'icon' => $type->icon(),
                'status' => $global?->published_at ? 'published' : 'draft',
                'hasChanges' => (bool) $global?->hasUnpublishedChanges(),
            ],
            'fields' => $type->fieldSet()->toSchema(),
            'data' => (object) $type->fieldSet()->normalize($data),
            'can' => ['publish' => $request->user()->can('content.publish')],
            'urls' => [
                'index' => route('content.admin.globals.index'),
                'update' => route('content.admin.globals.update', $type->key()),
                'media' => MediaController::urls(),
                'mediaAccept' => MediaController::acceptAttribute(),
            ],
        ]);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $this->type($key);

        $validated = $request->validate([
            'data' => ['present', 'array'],
            'publish' => ['nullable', 'boolean'],
        ]);

        $global = $this->content->saveGlobalDraft($key, $validated['data']);

        if ($request->boolean('publish')) {
            abort_unless($request->user()->can('content.publish'), 403);

            $global->publish($request->user()->getAuthIdentifier());

            return back()->with('content.success', __('content::admin.global_published'));
        }

        return back()->with('content.success', __('content::admin.draft_saved'));
    }

    protected function type(string $key): GlobalType
    {
        return $this->content->globals()->get($key) ?? abort(404);
    }
}
