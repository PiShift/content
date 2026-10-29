<?php

namespace PiShift\Content\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PiShift\Content\Models\Entry;
use PiShift\Content\Models\GlobalSet;
use PiShift\Content\Models\Media;
use PiShift\Content\Models\Page;

/**
 * Content written to a folder (content.json + the media files) and read back, so content
 * prepared on one install (pages, globals, collection entries, media) ships with a release.
 *
 * Importing never overwrites what a site already has: a page or global that exists, a
 * collection that has entries, or a media id that is taken is kept unless $force is used.
 *
 * @internal Not part of the public API; may change in any release.
 */
class ContentSnapshot
{
    public const FILE = 'content.json';

    /**
     * @param  list<string>  $except  Items to leave out: "page:{key}", "global:{key}" or "collection:{key}".
     * @return array{pages: int, globals: int, entries: int, media: int}
     */
    public function export(string $directory, array $except = []): array
    {
        $media = Media::query()->orderBy('id')->get();

        File::ensureDirectoryExists($directory.'/media');

        foreach ($media as $item) {
            $disk = Storage::disk($item->disk);

            if ($disk->exists($item->path)) {
                File::ensureDirectoryExists(dirname($directory.'/media/'.$item->path));
                File::put($directory.'/media/'.$item->path, $disk->get($item->path));
            }
        }

        $snapshot = [
            'version' => 1,
            'pages' => Page::query()->orderBy('position')->orderBy('key')->get()
                ->reject(fn (Page $page): bool => in_array("page:{$page->key}", $except, true))
                ->map(fn (Page $page): array => $page->only(['key', 'title', 'slug', 'template', 'draft', 'published', 'seo', 'status', 'position']) + ['published_at' => $page->published_at?->toIso8601String()])
                ->values()->all(),
            'globals' => GlobalSet::query()->orderBy('key')->get()
                ->reject(fn (GlobalSet $global): bool => in_array("global:{$global->key}", $except, true))
                ->map(fn (GlobalSet $global): array => $global->only(['key', 'draft', 'published']) + ['published_at' => $global->published_at?->toIso8601String()])
                ->values()->all(),
            'entries' => Entry::query()->orderBy('collection')->orderBy('id')->get()
                ->reject(fn (Entry $entry): bool => in_array("collection:{$entry->collection}", $except, true))
                ->map(fn (Entry $entry): array => $entry->only(['collection', 'data', 'position']))
                ->values()->all(),
            'media' => $media->map(fn (Media $item): array => $item->only(['id', 'disk', 'path', 'name', 'mime', 'size', 'width', 'height', 'alt']))->all(),
        ];

        File::put($directory.'/'.self::FILE, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");

        return ['pages' => count($snapshot['pages']), 'globals' => count($snapshot['globals']), 'entries' => count($snapshot['entries']), 'media' => count($snapshot['media'])];
    }

    /**
     * @return array{imported: list<string>, kept: list<string>}
     */
    public function import(string $directory, bool $force = false): array
    {
        $snapshot = json_decode(File::get($directory.'/'.self::FILE), true, flags: JSON_THROW_ON_ERROR);
        $result = ['imported' => [], 'kept' => []];

        DB::transaction(function () use ($snapshot, $directory, $force, &$result): void {
            foreach ($snapshot['media'] ?? [] as $item) {
                $existing = Media::query()->find($item['id']);

                if ($existing && ! $force) {
                    $result['kept'][] = "media:{$item['id']}";

                    continue;
                }

                $source = $directory.'/media/'.$item['path'];

                if (File::exists($source) && ! Storage::disk($item['disk'])->exists($item['path'])) {
                    Storage::disk($item['disk'])->put($item['path'], File::get($source));
                }

                $media = $existing ?? new Media;
                $media->forceFill($item)->save();
                $result['imported'][] = "media:{$item['id']}";
            }

            foreach ($snapshot['globals'] ?? [] as $item) {
                $global = GlobalSet::query()->firstOrNew(['key' => $item['key']]);

                if ($global->exists && ! $force) {
                    $result['kept'][] = "global:{$item['key']}";

                    continue;
                }

                $global->fill($item)->save();
                $result['imported'][] = "global:{$item['key']}";
            }

            foreach ($snapshot['pages'] ?? [] as $item) {
                $page = Page::query()->firstOrNew(['key' => $item['key']]);

                if ($page->exists && ! $force) {
                    $result['kept'][] = "page:{$item['key']}";

                    continue;
                }

                $page->fill($item)->save();
                $result['imported'][] = "page:{$item['key']}";
            }

            foreach (collect($snapshot['entries'] ?? [])->groupBy('collection') as $collection => $entries) {
                $existing = Entry::query()->where('collection', $collection);

                if ($existing->exists() && ! $force) {
                    $result['kept'][] = "collection:{$collection}";

                    continue;
                }

                $existing->delete();

                foreach ($entries as $entry) {
                    Entry::query()->create($entry);
                }

                $result['imported'][] = "collection:{$collection}";
            }
        });

        foreach ($snapshot['globals'] ?? [] as $item) {
            GlobalCache::forget($item['key']);
        }

        return $result;
    }
}
