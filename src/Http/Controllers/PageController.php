<?php

namespace PiShift\Content\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use PiShift\Content\ContentManager;

/**
 * Serves pages created in the admin, on any path the application doesn't route itself.
 */
class PageController
{
    public function __invoke(Request $request, ContentManager $content): View
    {
        $page = $content->pageBySlug($request->path() === '/' ? '' : $request->path());

        abort_if($page === null, 404);

        return view($page->templateView(), ['page' => $page]);
    }
}
