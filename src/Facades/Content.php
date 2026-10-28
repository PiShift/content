<?php

namespace PiShift\Content\Facades;

use Illuminate\Support\Facades\Facade;
use PiShift\Content\ContentManager;

/**
 * @method static \PiShift\Content\Pages\ResolvedPage|null page(string $key)
 * @method static \PiShift\Content\Pages\ResolvedPage|null pageBySlug(string $slug)
 * @method static \PiShift\Content\Pages\ResolvedPage|null resolve(\PiShift\Content\Models\Page $page, ?bool $draft = null)
 * @method static \PiShift\Content\Models\Page saveDraft(\PiShift\Content\Models\Page $page, array $document)
 * @method static \PiShift\Content\Models\GlobalSet saveGlobalDraft(string $key, array $data)
 * @method static mixed global(string $key, ?string $path = null, mixed $default = null)
 * @method static string previewUrl(\PiShift\Content\Models\Page $page)
 * @method static bool previewing()
 * @method static \PiShift\Content\Sections\SectionRegistry sections()
 * @method static \PiShift\Content\Globals\GlobalRegistry globals()
 *
 * @see ContentManager
 */
class Content extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ContentManager::class;
    }
}
