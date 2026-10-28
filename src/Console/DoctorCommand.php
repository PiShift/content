<?php

namespace PiShift\Content\Console;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use PiShift\Content\Collections\CollectionRegistry;
use PiShift\Content\Collections\CollectionType;
use PiShift\Content\Document\PageDocument;
use PiShift\Content\Fields\Field;
use PiShift\Content\Fields\Repeater;
use PiShift\Content\Globals\GlobalRegistry;
use PiShift\Content\Models\GlobalSet;
use PiShift\Content\Models\Page;
use PiShift\Content\Sections\SectionRegistry;
use PiShift\Content\Support\Localizer;
use Throwable;

/**
 * Checks that the code (types, views, templates) and the stored content still agree,
 * e.g. after a package update or a change to a section type.
 *
 * @internal Not part of the public API; may change in any release.
 */
class DoctorCommand extends Command
{
    protected $signature = 'content:doctor {--render : Also request every published page and expect a 200}';

    protected $description = 'Check section types, views, templates and stored content';

    /** @var list<array{0: string, 1: string, 2: string}> */
    protected array $problems = [];

    public function handle(SectionRegistry $sections, GlobalRegistry $globals, CollectionRegistry $collections): int
    {
        $localizer = Localizer::fromConfig();

        if (config('content.admin.assets') === 'prebuilt' && ! is_file(public_path('vendor/content/manifest.json'))) {
            $this->problem('error', 'admin', 'Prebuilt admin assets are not published: run `php artisan vendor:publish --tag=content-assets --force`.');
        }

        foreach ($sections->all() as $type) {
            $this->checkFields("section {$type->key()}", $type->fields());

            if (! View::exists($type->view())) {
                $this->problem('error', "section {$type->key()}", "View [{$type->view()}] is missing.");
            }
        }

        foreach ($globals->all() as $type) {
            $this->checkFields("global {$type->key()}", $type->fields());
        }

        foreach ($collections->all() as $type) {
            $this->checkCollection($type);
        }

        foreach (Page::query()->orderBy('key')->get() as $page) {
            $this->checkPage($page, $sections, $localizer);
        }

        foreach (GlobalSet::query()->orderBy('key')->get() as $global) {
            $type = $globals->get($global->key);

            if (! $type) {
                $this->problem('warning', "global {$global->key}", 'Stored, but no global type has this key.');

                continue;
            }

            foreach (['draft', 'published'] as $state) {
                if (! is_array($global->{$state})) {
                    continue;
                }

                $data = $type->fieldSet()->normalize($global->{$state}['data'] ?? []);
                $validator = Validator::make(['data' => $data], $type->fieldSet()->rules('data', $localizer));

                foreach ($validator->errors()->all() as $message) {
                    $this->problem('error', "global {$global->key} ({$state})", $message);
                }
            }
        }

        if ($this->option('render')) {
            $this->renderPages();
        }

        return $this->report();
    }

    /**
     * @param  list<Field>  $fields
     */
    protected function checkFields(string $owner, array $fields): void
    {
        $names = array_map(fn (Field $field): string => $field->getName(), $fields);

        foreach (array_unique(array_diff_assoc($names, array_unique($names))) as $duplicate) {
            $this->problem('error', $owner, "Field [{$duplicate}] is defined twice.");
        }

        foreach ($fields as $field) {
            try {
                $field->toSchema();
            } catch (Throwable $exception) {
                $this->problem('error', $owner, "Field [{$field->getName()}] can't describe itself: {$exception->getMessage()}");
            }

            if ($field instanceof Repeater) {
                $this->checkFields("{$owner} › {$field->getName()}", $field->getFields());
            }
        }
    }

    protected function checkCollection(CollectionType $type): void
    {
        $owner = "collection {$type->key()}";
        $this->checkFields($owner, $type->fields());

        $names = array_map(fn (Field $field): string => $field->getName(), $type->fields());

        if (! in_array($type->titleField(), $names, true)) {
            $this->problem('error', $owner, "Title field [{$type->titleField()}] is not one of its fields.");
        }

        foreach (array_keys($type->filters()) as $filter) {
            if (! in_array($filter, $names, true)) {
                $this->problem('warning', $owner, "Filter [{$filter}] is not one of its fields.");
            }
        }
    }

    protected function checkPage(Page $page, SectionRegistry $sections, Localizer $localizer): void
    {
        $templates = config('content.templates', []);

        if ($templates !== [] && ! array_key_exists((string) $page->template, $templates)) {
            $this->problem('error', "page {$page->key}", "Template [{$page->template}] is not configured.");
        }

        foreach (['draft', 'published'] as $state) {
            if (! is_array($page->{$state})) {
                continue;
            }

            $document = PageDocument::fromArray($page->{$state})->normalize($sections);

            foreach ($document->sections() as $index => $section) {
                if (! $sections->has((string) ($section['type'] ?? ''))) {
                    $this->problem('warning', "page {$page->key} ({$state})", "Section #{$index} has unknown type [".($section['type'] ?? '?').'] and is skipped on the site.');
                }
            }

            $validator = Validator::make($document->toArray(), $document->rules($sections, $localizer));

            foreach ($validator->errors()->messages() as $key => $messages) {
                $this->problem('error', "page {$page->key} ({$state})", "{$key}: {$messages[0]}");
            }
        }
    }

    /**
     * Request every published page through the HTTP kernel (default locale).
     */
    protected function renderPages(): void
    {
        foreach (Page::query()->whereNotNull('published_at')->orderBy('key')->get() as $page) {
            $path = $page->path(config('content.locales.0', 'ar'));

            if ($path === null) {
                continue;
            }

            try {
                $status = app()->handle(Request::create($path))->getStatusCode();
            } catch (Throwable $exception) {
                $status = $exception->getMessage();
            }

            if ($status !== 200) {
                $this->problem('error', "page {$page->key}", "GET {$path} returned {$status}.");
            }
        }
    }

    protected function problem(string $level, string $owner, string $message): void
    {
        $this->problems[] = [$level, $owner, $message];
    }

    protected function report(): int
    {
        if ($this->problems === []) {
            $this->components->info('Content is healthy: types, views, templates and stored content agree.');

            return self::SUCCESS;
        }

        $this->table(['Level', 'Where', 'Problem'], $this->problems);

        $errors = count(array_filter($this->problems, fn (array $problem): bool => $problem[0] === 'error'));
        $warnings = count($this->problems) - $errors;

        $errors > 0
            ? $this->components->error("{$errors} error(s), {$warnings} warning(s).")
            : $this->components->warn("{$warnings} warning(s).");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
