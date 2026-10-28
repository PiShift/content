<?php

namespace PiShift\Content\Http\Controllers\Admin;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use PiShift\Content\Models\Media;

/**
 * Media library: the page, plus JSON endpoints used by the image/file pickers.
 */
class MediaController
{
    public function index(Request $request): Response
    {
        return Inertia::render('Media/Index', [
            'media' => $this->paginate($request),
            'filters' => ['q' => (string) $request->query('q', ''), 'type' => (string) $request->query('type', '')],
            'urls' => $this->urls(),
            'accept' => $this->acceptAttribute(),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        return response()->json($this->paginate($request));
    }

    public function show(Media $media): JsonResponse
    {
        return response()->json($media->toAdminArray());
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:'.config('content.media.max_size', 20480),
                'extensions:'.implode(',', config('content.media.extensions', [])),
            ],
        ]);

        $file = $request->file('file');
        $disk = config('content.media.disk', 'public');
        $extension = strtolower($file->getClientOriginalExtension());
        $directory = trim(config('content.media.directory', 'content'), '/').'/'.now()->format('Y/m');
        $path = $file->storeAs($directory, Str::uuid().'.'.$extension, $disk);

        [$width, $height] = str_starts_with((string) $file->getMimeType(), 'image/') ? (@getimagesize($file->getRealPath()) ?: [null, null]) : [null, null];

        $media = Media::create([
            'disk' => $disk,
            'path' => $path,
            'name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
        ]);

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json($media->toAdminArray(), 201);
        }

        return back()->with('content.success', __('content::admin.media_uploaded'));
    }

    public function update(Request $request, Media $media): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:250'],
            'alt' => ['nullable', 'array'],
            'alt.*' => ['nullable', 'string', 'max:255'],
        ]);

        $media->update($validated);

        return back()->with('content.success', __('content::admin.media_saved'));
    }

    public function destroy(Media $media): RedirectResponse
    {
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        return back()->with('content.success', __('content::admin.media_deleted'));
    }

    protected function paginate(Request $request): LengthAwarePaginator
    {
        return Media::query()
            ->when($request->query('q'), fn (Builder $query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($request->query('type') === 'image', fn (Builder $query) => $query->where('mime', 'like', 'image/%'))
            ->when($request->query('type') === 'file', fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNull('mime')->orWhere('mime', 'not like', 'image/%')))
            ->latest('id')
            ->paginate(24)
            ->withQueryString()
            ->through(fn (Media $media): array => $media->toAdminArray());
    }

    /**
     * @return array<string, string>
     */
    public static function urls(): array
    {
        return [
            'index' => route('content.admin.media.index'),
            'list' => route('content.admin.media.list'),
            'show' => route('content.admin.media.show', ['media' => '__ID__']),
            'store' => route('content.admin.media.store'),
            'update' => route('content.admin.media.update', ['media' => '__ID__']),
            'destroy' => route('content.admin.media.destroy', ['media' => '__ID__']),
        ];
    }

    public static function acceptAttribute(): string
    {
        return implode(',', array_map(fn (string $extension): string => ".{$extension}", config('content.media.extensions', [])));
    }
}
