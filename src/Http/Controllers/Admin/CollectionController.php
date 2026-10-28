<?php

namespace PiShift\Content\Http\Controllers\Admin;

use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;
use PiShift\Content\Collections\CollectionRegistry;
use PiShift\Content\Collections\CollectionType;
use PiShift\Content\Fields\Field;
use PiShift\Content\Fields\FieldSet;
use PiShift\Content\Fields\File;
use PiShift\Content\Fields\Select;
use PiShift\Content\Fields\Text;
use PiShift\Content\Fields\Upload;
use PiShift\Content\Support\Localizer;

/**
 * @internal Not part of the public API; may change in any release.
 */
class CollectionController
{
    public function __construct(protected CollectionRegistry $collections) {}

    public function index(Request $request, string $collection): Response
    {
        $type = $this->collection($request, $collection);
        $filters = array_intersect_key((array) $request->query('filters', []), $type->filters());
        $search = (string) $request->query('search', '');
        $driver = $type->driver();

        $records = $driver->paginate($type, $search, array_map('strval', $filters));

        return Inertia::render('Collections/Index', [
            'collection' => $type->toSchema(),
            'records' => $records->through(fn (object $record): array => [
                'id' => $driver->id($record),
                'cells' => $this->cells($type, $driver->data($type, $record)),
                'editUrl' => route('content.admin.collections.edit', [$type->key(), $driver->id($record)]),
            ]),
            'query' => ['search' => $search, 'filters' => (object) $filters],
            'urls' => [
                'index' => route('content.admin.collections.index', $type->key()),
                'create' => $type->canCreate() ? route('content.admin.collections.create', $type->key()) : null,
            ],
        ]);
    }

    public function create(Request $request, string $collection): Response
    {
        $type = $this->collection($request, $collection);
        abort_unless($type->canCreate(), 403);

        return $this->form($type, null, $type->fieldSet()->normalize([]));
    }

    public function store(Request $request, string $collection): RedirectResponse
    {
        $type = $this->collection($request, $collection);
        abort_unless($type->canCreate(), 403);

        $record = $type->driver()->save($type, null, $this->validated($request, $type, null));
        $type->saved($record, true);

        return redirect()
            ->route('content.admin.collections.edit', [$type->key(), $type->driver()->id($record)])
            ->with('content.success', __('content::admin.record_saved'));
    }

    public function edit(Request $request, string $collection, string $record): Response
    {
        $type = $this->collection($request, $collection);
        $model = $type->driver()->find($type, $record) ?? abort(404);

        return $this->form($type, $model, $type->driver()->data($type, $model));
    }

    public function update(Request $request, string $collection, string $record): RedirectResponse
    {
        $type = $this->collection($request, $collection);
        $model = $type->driver()->find($type, $record) ?? abort(404);

        $type->driver()->save($type, $model, $this->validated($request, $type, $model));
        $type->saved($model, false);

        return back()->with('content.success', __('content::admin.record_saved'));
    }

    public function destroy(Request $request, string $collection, string $record): RedirectResponse
    {
        $type = $this->collection($request, $collection);
        $model = $type->driver()->find($type, $record) ?? abort(404);
        abort_unless($type->canDelete($model), 403);

        $type->driver()->delete($type, $model);

        return redirect()
            ->route('content.admin.collections.index', $type->key())
            ->with('content.success', __('content::admin.record_deleted'));
    }

    public function action(Request $request, string $collection, string $record, string $action): RedirectResponse
    {
        $type = $this->collection($request, $collection);
        $model = $type->driver()->find($type, $record) ?? abort(404);
        $handler = $type->action($action) ?? abort(404);
        abort_unless($handler->isVisibleFor($model), 403);

        $message = $handler->run($model);

        return back()->with('content.success', $message ?? __('content::admin.action_done'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function form(CollectionType $type, ?object $record, array $data): Response
    {
        $id = $record ? $type->driver()->id($record) : null;

        // Stored passwords never go back to the browser.
        foreach ($type->fieldSet()->fields() as $field) {
            if ($field instanceof Text && $field->isPassword()) {
                $data[$field->getName()] = null;
            }
        }

        return Inertia::render('Collections/Form', [
            'collection' => $type->toSchema(),
            'record' => ['id' => $id, 'data' => (object) $data],
            'actions' => $record === null ? [] : collect($type->actions())
                ->filter(fn ($action): bool => $action->isVisibleFor($record))
                ->map(fn ($action): array => [...$action->toSchema(), 'url' => route('content.admin.collections.action', [$type->key(), $id, $action->key()])])
                ->values()
                ->all(),
            'urls' => [
                'index' => route('content.admin.collections.index', $type->key()),
                'save' => $id === null
                    ? route('content.admin.collections.store', $type->key())
                    : route('content.admin.collections.update', [$type->key(), $id]),
                'destroy' => $id === null || ! $type->canDelete($record) ? null : route('content.admin.collections.destroy', [$type->key(), $id]),
                'media' => MediaController::urls(),
                'mediaAccept' => MediaController::acceptAttribute(),
            ],
        ]);
    }

    /**
     * Validated values of the collection's writable fields, with new uploads stored. A blank
     * password keeps the current one; an upload field keeps its file unless a new one is sent.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, CollectionType $type, ?object $record): array
    {
        $data = $type->fieldSet()->normalize((array) $request->input('data', []));
        $current = $record ? $type->driver()->data($type, $record) : [];
        $fields = [];

        foreach ($type->fieldSet()->writable() as $field) {
            $name = $field->getName();

            if ($field instanceof Upload) {
                $data[$name] = $request->file("data.{$name}") ?? ($current[$name] ?? null);
            }

            if ($record && $field instanceof Text && $field->isPassword() && blank($data[$name] ?? null)) {
                continue;
            }

            $fields[] = $field;
        }

        $fieldSet = new FieldSet($fields);
        $rules = $fieldSet->rules('data', Localizer::fromConfig());

        $kept = array_map(fn (Field $field): string => $field->getName(), $fields);
        $skipped = array_diff(array_map(fn (Field $field): string => $field->getName(), $type->fieldSet()->fields()), $kept);

        foreach ($type->rules($record ? $type->driver()->id($record) : null) as $field => $fieldRules) {
            if (in_array($field, $skipped, true)) {
                continue;
            }

            $rules["data.{$field}"] = [...($rules["data.{$field}"] ?? []), ...$fieldRules];
        }

        Validator::make(['data' => $data], $rules)->validate();

        // Only the collection's own fields are stored.
        $validated = array_intersect_key($data, array_flip($kept));

        foreach ($fields as $field) {
            $value = $validated[$field->getName()] ?? null;

            if ($field instanceof Upload && $value instanceof UploadedFile) {
                $validated[$field->getName()] = $field->store($value);

                if ($field->getFileNameAttribute()) {
                    $validated[$field->getFileNameAttribute()] = $value->getClientOriginalName();
                }
            }
        }

        return $validated;
    }

    /**
     * Display values of a record's list columns, in the admin language.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, array{type: string, value: mixed, tone?: string}>
     */
    protected function cells(CollectionType $type, array $data): array
    {
        $fields = collect($type->fieldSet()->fields())->keyBy(fn (Field $field): string => $field->getName());
        $badges = $type->badges();
        $localizer = Localizer::fromConfig(app()->getLocale());
        $cells = [];

        foreach (array_keys($type->columns()) as $name) {
            $field = $fields->get($name);
            $value = $data[$name] ?? null;

            $cells[$name] = match (true) {
                $field === null => ['type' => 'text', 'value' => is_scalar($value) ? (string) $value : null],
                $field instanceof Upload => ['type' => 'file', 'value' => $value],
                $field instanceof File => ['type' => $field->type(), 'value' => $field->resolve($value, $localizer)['url'] ?? null],
                $field instanceof Select => ['type' => 'text', 'value' => $field->toSchema()['options'][(string) $value] ?? $value],
                $field->type() === 'toggle' => ['type' => 'boolean', 'value' => (bool) $value],
                in_array($field->type(), ['date', 'datetime'], true) => ['type' => 'date', 'value' => $value instanceof DateTimeInterface ? $value->format('c') : (filled($value) ? Carbon::parse($value)->toIso8601String() : null)],
                default => ['type' => 'text', 'value' => is_array($value) ? $localizer->pick($value) : $value],
            };

            if (isset($badges[$name])) {
                $cells[$name] = ['type' => 'badge', 'value' => $cells[$name]['value'], 'tone' => $badges[$name][(string) $value] ?? 'neutral'];
            }

            if (is_string($cells[$name]['value'])) {
                $cells[$name]['value'] = mb_strimwidth(strip_tags($cells[$name]['value']), 0, 120, '…');
            }
        }

        return $cells;
    }

    protected function collection(Request $request, string $key): CollectionType
    {
        $type = $this->collections->get($key) ?? abort(404);

        abort_unless($request->user()->can($type->ability()), 403);

        return $type;
    }
}
