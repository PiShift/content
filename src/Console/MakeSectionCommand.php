<?php

namespace PiShift\Content\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeSectionCommand extends Command
{
    protected $signature = 'content:make-section {name : e.g. Cards or CardsSection} {--force : Overwrite existing files}';

    protected $description = 'Create a section type class and its Blade view';

    public function handle(Filesystem $files): int
    {
        $name = Str::studly(Str::beforeLast($this->argument('name'), 'Section'));
        $key = Str::kebab($name);
        $namespace = config('content.discover.sections.namespace', 'App\\Content\\Sections');

        $targets = [
            config('content.discover.sections.path', app_path('Content/Sections'))."/{$name}Section.php" => 'section.php.stub',
            resource_path('views/'.str_replace('.', '/', config('content.section_views', 'content.sections'))."/{$key}.blade.php") => 'section.blade.php.stub',
        ];

        foreach ($targets as $path => $stub) {
            if ($files->exists($path) && ! $this->option('force')) {
                $this->components->error("{$path} already exists.");

                return self::FAILURE;
            }

            $files->ensureDirectoryExists(dirname($path));
            $files->put($path, str_replace(
                ['{{ namespace }}', '{{ class }}', '{{ key }}', '{{ label }}'],
                [$namespace, "{$name}Section", $key, Str::headline($name)],
                $files->get(__DIR__."/../../stubs/{$stub}"),
            ));

            $this->components->info("Created {$path}");
        }

        return self::SUCCESS;
    }
}
