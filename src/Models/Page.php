<?php

namespace PiShift\Content\Models;

use Illuminate\Database\Eloquent\Model;
use PiShift\Content\Document\PageDocument;
use PiShift\Content\Models\Concerns\HasDraftAndPublished;

/**
 * @property array<string, mixed>|null $draft
 * @property array<string, mixed>|null $published
 * @property array<string, string>|null $title
 * @property array<string, string>|null $slug
 */
class Page extends Model
{
    use HasDraftAndPublished {
        publish as publishDraft;
    }

    protected $table = 'content_pages';

    protected $fillable = ['key', 'title', 'slug', 'template', 'draft', 'published', 'seo', 'status', 'published_at', 'position'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'slug' => 'array',
            'draft' => 'array',
            'published' => 'array',
            'seo' => 'array',
            'published_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    public function draftDocument(): PageDocument
    {
        return PageDocument::fromArray($this->draft);
    }

    public function publishedDocument(): PageDocument
    {
        return PageDocument::fromArray($this->published);
    }

    public function isPublished(): bool
    {
        return $this->published !== null && $this->published_at !== null;
    }

    public function path(?string $locale = null): ?string
    {
        $slug = $this->slug[$locale ?? app()->getLocale()] ?? collect($this->slug ?? [])->first(fn (?string $slug): bool => $slug !== null);

        return $slug === null ? null : '/'.ltrim($slug, '/');
    }

    public function publish(?int $userId = null): static
    {
        $this->status = 'published';

        return $this->publishDraft($userId);
    }
}
