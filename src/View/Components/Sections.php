<?php

namespace PiShift\Content\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use PiShift\Content\Pages\ResolvedPage;

/**
 * Renders the visible sections of a page: <x-content::sections :page="$page" />.
 */
class Sections extends Component
{
    public function __construct(public ?ResolvedPage $page = null) {}

    public function render(): View
    {
        return view('content::components.sections');
    }
}
