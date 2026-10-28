<?php

namespace PiShift\Content\Admin;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

/**
 * Widgets of the admin home page. Projects register them (e.g. in a service provider);
 * each callback returns the widget's data, computed only for users with its ability:
 *
 * - ['type' => 'stats', 'items' => [['label', 'value', 'description'?, 'icon'?, 'tone'?, 'url'?], …]]
 * - ['type' => 'chart', 'title', 'labels' => [...], 'series' => [['label', 'data' => [...]], …]]
 * - ['type' => 'table', 'title', 'columns' => [key => label], 'rows' => [['cells' => [key => ['type', 'value', 'tone'?]], 'url'?], …]]
 */
class Dashboard
{
    /** @var array<string, array{resolve: Closure, ability: ?string, sort: int, width: string}> */
    protected array $widgets = [];

    /**
     * @param  Closure(): array<string, mixed>  $resolve
     * @param  'full'|'half'  $width
     */
    public function add(string $key, Closure $resolve, ?string $ability = null, int $sort = 100, string $width = 'full'): static
    {
        $this->widgets[$key] = compact('resolve', 'ability', 'sort', 'width');

        return $this;
    }

    public function remove(string $key): static
    {
        unset($this->widgets[$key]);

        return $this;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forUser(?Authenticatable $user): array
    {
        return collect($this->widgets)
            ->filter(fn (array $widget): bool => $widget['ability'] === null || Gate::forUser($user)->allows($widget['ability']))
            ->sortBy('sort')
            ->map(fn (array $widget, string $key): array => ['key' => $key, 'width' => $widget['width'], ...($widget['resolve'])()])
            ->values()
            ->all();
    }
}
