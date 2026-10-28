<?php

namespace PiShift\Content\Fields;

use Closure;
use PiShift\Content\Models\Media;
use PiShift\Content\Support\Localizer;

/**
 * A media library item (stored as its id). A string is also accepted: an absolute URL, a
 * site path ("/images/logo.png"), or a path on the public disk, used for imported content.
 */
class File extends Field
{
    /** @var list<string> */
    protected array $accept = [];

    protected bool $storesPath = false;

    /**
     * Store the file's path on the media disk instead of its media id, for models that keep
     * a path column (existing tables).
     */
    public function storesPath(bool $storesPath = true): static
    {
        $this->storesPath = $storesPath;

        return $this;
    }

    /**
     * @param  list<string>  $mimeTypes  e.g. ['application/pdf']
     */
    public function accept(array $mimeTypes): static
    {
        $this->accept = $mimeTypes;

        return $this;
    }

    public function type(): string
    {
        return 'file';
    }

    protected function valueRules(): array
    {
        return [function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_int($value) && ! (is_string($value) && strlen($value) <= 2048)) {
                $fail(__('validation.string', ['attribute' => $attribute]));
            }
        }];
    }

    protected function resolveValue(mixed $value, Localizer $localizer): mixed
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            $media = Media::query()->find((int) $value);

            return $media ? $this->fromMedia($media, $localizer) : null;
        }

        return $this->fromPath((string) $value);
    }

    /**
     * @return array<string, mixed>
     */
    protected function fromMedia(Media $media, Localizer $localizer): array
    {
        return [
            'url' => $media->url,
            'name' => $media->name,
            'mime' => $media->mime,
            'size' => $media->size,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function fromPath(string $path): array
    {
        $url = match (true) {
            str_starts_with($path, 'http://'), str_starts_with($path, 'https://') => $path,
            str_starts_with($path, '/') => asset(ltrim($path, '/')),
            default => asset('storage/'.$path),
        };

        return ['url' => $url, 'name' => basename($path), 'mime' => null, 'size' => null];
    }

    protected function extraSchema(): array
    {
        return ['accept' => $this->accept, 'storesPath' => $this->storesPath];
    }
}
