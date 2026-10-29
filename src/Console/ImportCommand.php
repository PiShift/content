<?php

namespace PiShift\Content\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PiShift\Content\Support\ContentSnapshot;

/**
 * Reads a folder written by content:export. Content the site already has is kept unless
 * --force is used, so it is safe to run on every deploy.
 *
 * @internal Not part of the public API; may change in any release.
 */
class ImportCommand extends Command
{
    protected $signature = 'content:import
        {--path=database/content : Folder written by content:export}
        {--force : Replace pages, globals, collection entries and media that already exist}';

    protected $description = 'Import content exported with content:export, keeping what the site already has';

    public function handle(ContentSnapshot $snapshot): int
    {
        $directory = base_path($this->option('path'));

        if (! File::exists($directory.'/'.ContentSnapshot::FILE)) {
            $this->components->warn("Nothing to import: {$this->option('path')}/".ContentSnapshot::FILE.' does not exist.');

            return self::SUCCESS;
        }

        $result = $snapshot->import($directory, (bool) $this->option('force'));

        foreach ($result['imported'] as $item) {
            $this->components->twoColumnDetail($item, '<fg=green>imported</>');
        }

        foreach ($result['kept'] as $item) {
            $this->components->twoColumnDetail($item, '<fg=yellow>already exists, kept</>');
        }

        return self::SUCCESS;
    }
}
