<?php

namespace PiShift\Content\Admin;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

/**
 * Menu of the admin shell. The package adds its own entries; projects add theirs
 * (e.g. in a service provider) and each entry is shown only to users with its ability.
 */
class Navigation
{
    /** @var array<string, array{label: string, url: Closure|string, icon: string, ability: ?string, group: ?string, active: Closure|string, sort: int}> */
    protected array $items = [];

    /**
     * @param  Closure(): string|string  $url
     * @param  Closure(): bool|string  $active  route name pattern marking the entry active, e.g. "content.admin.pages.*", or a callback
     */
    public function add(string $key, string $label, Closure|string $url, string $icon = 'square', ?string $ability = null, ?string $group = null, Closure|string $active = '', int $sort = 100): static
    {
        $this->items[$key] = compact('label', 'url', 'icon', 'ability', 'group', 'active', 'sort');

        return $this;
    }

    public function remove(string $key): static
    {
        unset($this->items[$key]);

        return $this;
    }

    /**
     * @return list<array{key: string, label: string, url: string, icon: string, group: ?string, active: bool}>
     */
    public function forUser(?Authenticatable $user): array
    {
        return collect($this->items)
            ->filter(fn (array $item): bool => $item['ability'] === null || Gate::forUser($user)->allows($item['ability']))
            ->sortBy('sort')
            ->map(fn (array $item, string $key): array => [
                'key' => $key,
                'label' => __($item['label']),
                'url' => $item['url'] instanceof Closure ? ($item['url'])() : $item['url'],
                'icon' => $item['icon'],
                'group' => $item['group'] ? __($item['group']) : null,
                'active' => $item['active'] instanceof Closure ? (bool) ($item['active'])() : ($item['active'] !== '' && request()->routeIs($item['active'])),
            ])
            ->values()
            ->all();
    }
}
