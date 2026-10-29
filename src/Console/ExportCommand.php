<?php

namespace PiShift\Content\Console;

use Illuminate\Console\Command;
use PiShift\Content\Support\ContentSnapshot;

/**
 * Writes the site's content (pages, globals, collection entries, media) to a folder that
 * ships with the release; content:import reads it on the server.
 *
 * @internal Not part of the public API; may change in any release.
 */
class ExportCommand extends Command
{
    protected $signature = 'content:export
        {--path=database/content : Folder to write content.json and the media files to}
        {--except=* : Leave out an item: global:site, page:about or collection:faq}';

    protected $description = 'Export pages, globals, collection entries and media to a folder';

    public function handle(ContentSnapshot $snapshot): int
    {
        $counts = $snapshot->export(base_path($this->option('path')), $this->option('except'));

        $this->components->info(sprintf('Exported %d pages, %d globals, %d entries and %d media files to %s.', $counts['pages'], $counts['globals'], $counts['entries'], $counts['media'], $this->option('path')));

        return self::SUCCESS;
    }
}
