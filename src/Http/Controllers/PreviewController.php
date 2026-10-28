<?php

namespace PiShift\Content\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use PiShift\Content\ContentManager;
use PiShift\Content\Models\Page;

/**
 * Renders a page's draft (and draft globals) behind a signed URL, for the admin preview.
 *
 * @internal Not part of the public API; may change in any release.
 */
class PreviewController
{
    public function __invoke(Request $request, Page $page, ContentManager $content): View
    {
        if ($request->filled('locale') && in_array($request->query('locale'), config('content.locales', []), true)) {
            app()->setLocale($request->query('locale'));
        }

        $request->attributes->set(ContentManager::PREVIEW_ATTRIBUTE, true);

        $resolved = $content->resolve($page, draft: true);

        return view($resolved->templateView(), ['page' => $resolved]);
    }
}
