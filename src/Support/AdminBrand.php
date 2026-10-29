<?php

namespace PiShift\Content\Support;

use Illuminate\Support\Str;

/**
 * The project's colour and logo for the admin (config content.admin.brand). The colour is
 * printed as CSS variables at page load, so the prebuilt admin needs no rebuild.
 */
class AdminBrand
{
    /**
     * The configured accent as #rrggbb, or null (invalid values are ignored).
     */
    public function accent(): ?string
    {
        $accent = config('content.admin.brand.accent');

        return is_string($accent) && preg_match('/^#[0-9a-f]{6}$/i', $accent) ? strtolower($accent) : null;
    }

    /**
     * The logo URL: a full URL, or a path in the public folder.
     */
    public function logo(): ?string
    {
        $logo = config('content.admin.brand.logo');

        if (! is_string($logo) || $logo === '') {
            return null;
        }

        return Str::startsWith($logo, ['http://', 'https://', '//']) ? $logo : asset(ltrim($logo, '/'));
    }

    /**
     * CSS overriding the accent tokens, or '' when no accent is configured. Hover and soft
     * shades are mixed from the accent; the text on it is white or ink, whichever reads better.
     */
    public function css(): string
    {
        $accent = $this->accent();

        if ($accent === null) {
            return '';
        }

        [$red, $green, $blue] = array_map(fn (string $hex): float => hexdec($hex) / 255, str_split(substr($accent, 1), 2));
        $channel = fn (float $value): float => $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $channel($red) + 0.7152 * $channel($green) + 0.0722 * $channel($blue);
        $onAccent = $luminance > 0.4 ? '#17211b' : '#ffffff';

        return ':root{'
            ."--color-accent:{$accent};"
            ."--color-accent-strong:color-mix(in oklab, {$accent} 80%, black);"
            ."--color-accent-soft:color-mix(in oklab, {$accent} 12%, white);"
            ."--color-on-accent:{$onAccent};"
            .'}';
    }
}
