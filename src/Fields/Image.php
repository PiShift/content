<?php

namespace PiShift\Content\Fields;

use PiShift\Content\Models\Media;
use PiShift\Content\Support\Localizer;

class Image extends File
{
    /** @var list<string> */
    protected array $accept = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'image/gif'];

    public function type(): string
    {
        return 'image';
    }

    protected function fromMedia(Media $media, Localizer $localizer): array
    {
        return [
            'url' => $media->url,
            'alt' => $localizer->pick($media->alt) ?? '',
            'width' => $media->width,
            'height' => $media->height,
        ];
    }

    protected function fromPath(string $path): array
    {
        return ['url' => parent::fromPath($path)['url'], 'alt' => '', 'width' => null, 'height' => null];
    }
}
