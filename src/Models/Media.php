<?php

namespace PiShift\Content\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * An uploaded image or document used by image and file fields.
 */
class Media extends Model
{
    protected $table = 'content_media';

    protected $fillable = ['disk', 'path', 'name', 'mime', 'size', 'width', 'height', 'alt'];

    protected function casts(): array
    {
        return [
            'alt' => 'array',
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk($this->disk)->url($this->path));
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'name' => $this->name,
            'mime' => $this->mime,
            'size' => $this->size,
            'width' => $this->width,
            'height' => $this->height,
            'alt' => $this->alt ?? [],
            'is_image' => $this->isImage(),
        ];
    }
}
