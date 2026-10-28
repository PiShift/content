<?php

namespace PiShift\Content\Console;

use Illuminate\Console\Command;
use PiShift\Content\Document\PageDocument;
use PiShift\Content\Globals\GlobalRegistry;
use PiShift\Content\Models\GlobalSet;
use PiShift\Content\Models\Page;
use PiShift\Content\Sections\SectionRegistry;
use PiShift\Content\Support\GlobalCache;

/**
 * Stored content is upgraded on read, so nothing breaks when a type's version goes up.
 * This writes the upgraded content back, so older upgrade() steps can be removed later.
 *
 * @internal Not part of the public API; may change in any release.
 */
class UpgradeCommand extends Command
{
    protected $signature = 'content:upgrade {--dry-run : List what would change without saving}';

    protected $description = 'Rewrite stored pages and globals saved by older section/global type versions';

    public function handle(SectionRegistry $sections, GlobalRegistry $globals): int
    {
        $changed = [];

        foreach (Page::query()->orderBy('key')->get() as $page) {
            foreach (['draft', 'published'] as $state) {
                $stored = $page->{$state};

                if (! is_array($stored) || ! $this->pageIsOutdated($stored, $sections)) {
                    continue;
                }

                $changed[] = ["page {$page->key}", $state];
                $page->{$state} = PageDocument::fromArray($stored)->normalize($sections)->toArray();
            }

            if (! $this->option('dry-run') && $page->isDirty()) {
                $page->save();
            }
        }

        foreach (GlobalSet::query()->orderBy('key')->get() as $global) {
            $type = $globals->get($global->key);

            if (! $type) {
                continue;
            }

            foreach (['draft', 'published'] as $state) {
                $stored = $global->{$state};

                if (! is_array($stored) || (int) ($stored['version'] ?? 1) >= $type->version()) {
                    continue;
                }

                $changed[] = ["global {$global->key}", $state];
                $data = $type->upgrade(is_array($stored['data'] ?? null) ? $stored['data'] : [], (int) ($stored['version'] ?? 1));
                $global->{$state} = ['version' => $type->version(), 'data' => $type->fieldSet()->normalize($data)];
            }

            if (! $this->option('dry-run') && $global->isDirty()) {
                $global->save();
                GlobalCache::forget($global->key);
            }
        }

        if ($changed === []) {
            $this->components->info('All stored content is already at the current type versions.');

            return self::SUCCESS;
        }

        $this->table(['Content', 'Version'], $changed);
        $this->option('dry-run')
            ? $this->components->warn(count($changed).' document(s) would be upgraded (dry run, nothing saved).')
            : $this->components->info(count($changed).' document(s) upgraded.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $document
     */
    protected function pageIsOutdated(array $document, SectionRegistry $sections): bool
    {
        if ((int) ($document['version'] ?? 1) < PageDocument::VERSION) {
            return true;
        }

        foreach (PageDocument::fromArray($document)->sections() as $section) {
            $type = $sections->get((string) ($section['type'] ?? ''));

            if ($type && (int) ($section['version'] ?? 1) < $type->version()) {
                return true;
            }
        }

        return false;
    }
}
