<?php

namespace PiShift\Content\Support;

use Illuminate\Support\Facades\Cache;
use PiShift\Content\ContentManager;

/**
 * Published globals are read on every request (header, footer…), so their stored content is
 * cached until the global is saved.
 */
class GlobalCache
{
    public static function key(string $global): string
    {
        return "content.global.{$global}";
    }

    public static function forget(string $global): void
    {
        Cache::forget(static::key($global));
        app(ContentManager::class)->forgetResolved($global);
    }
}
