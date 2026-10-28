<?php

use PiShift\Content\ContentManager;

if (! function_exists('content_global')) {
    /**
     * A value of a global for the current locale, e.g. content_global('site.phone').
     */
    function content_global(string $path, mixed $default = null): mixed
    {
        [$key, $rest] = array_pad(explode('.', $path, 2), 2, null);

        return app(ContentManager::class)->global($key, $rest, $default);
    }
}
