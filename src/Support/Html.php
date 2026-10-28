<?php

namespace PiShift\Content\Support;

/**
 * Keeps rich text to the formatting the editor produces.
 */
class Html
{
    public const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><s><a><ul><ol><li><h2><h3><h4><blockquote><span><sub><sup><hr><code><pre>';

    public static function clean(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        $html = strip_tags($html, self::ALLOWED_TAGS);

        // Event handlers and inline styles.
        $html = preg_replace('/\s(on\w+|style)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);

        // Script URLs in links.
        return preg_replace('/(href\s*=\s*["\']?)\s*(javascript|data|vbscript):[^"\'\s>]*/i', '$1#', $html);
    }
}
