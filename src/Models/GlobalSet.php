<?php

namespace PiShift\Content\Models;

use Illuminate\Database\Eloquent\Model;
use PiShift\Content\Models\Concerns\HasDraftAndPublished;
use PiShift\Content\Support\GlobalCache;

/**
 * Stored content of a global type: {"version": n, "data": {...}}.
 *
 * @property array<string, mixed>|null $draft
 * @property array<string, mixed>|null $published
 */
class GlobalSet extends Model
{
    use HasDraftAndPublished;

    protected $table = 'content_globals';

    protected $fillable = ['key', 'draft', 'published', 'published_at'];

    protected static function booted(): void
    {
        static::saved(fn (self $global) => GlobalCache::forget($global->key));
        static::deleted(fn (self $global) => GlobalCache::forget($global->key));
    }

    protected function casts(): array
    {
        return [
            'draft' => 'array',
            'published' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
