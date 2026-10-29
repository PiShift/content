# PiShift Content

Admin-managed content for Laravel sites. **Structure lives in code, content lives in the
database, and pages render with the site's own Blade (or React) components.** The admin is
a React + Inertia app that ships with the package.

- **Sections**: blocks a page is made of (hero, cards, text…), with typed fields, variants and style options.
- **Pages**: an ordered list of sections, with draft, publish, revisions and live preview.
- **Globals**: site-wide settings (identity, menus, theme).
- **Collections**: lists of records (news, documents…) stored in their own table or an existing Eloquent model, with list filters, badges, record actions and permissions.
- **Dashboard and menu**: widgets and menu entries that projects register.
- Languages per field (`ar`, `fr`, `en` by default) with fallbacks, RTL-aware admin in Arabic and French.

Requires PHP 8.3+, Laravel 12 or 13, `inertiajs/inertia-laravel` 3.

## Install

Until the package is on Packagist, add the private repository to the project's `composer.json`
and give Composer a GitHub token that can read it (`composer config --global github-oauth.github.com <token>`):

```json
"repositories": [{ "type": "vcs", "url": "https://github.com/PiShift/content" }]
```

```bash
composer require pishift/content
php artisan migrate
php artisan vendor:publish --tag=content-config
php artisan vendor:publish --tag=content-assets
```

Open `/admin` and sign in with a user of the `web` guard. Abilities decide what each user sees:

```php
// AppServiceProvider::boot()
Gate::define('content.manage', fn (User $user) => $user->is_admin);
Gate::define('content.publish', fn (User $user) => $user->is_admin);
```

Without definitions, any signed-in user may manage and publish.

## Sections

```bash
php artisan content:make-section Cards
```

creates `app/Content/Sections/CardsSection.php` and `resources/views/content/sections/cards.blade.php`.

```php
class CardsSection extends SectionType
{
    public function fields(): array
    {
        return [
            Field::text('title')->translatable()->required(),
            Field::repeater('cards', [
                Field::icon('icon')->options(['star' => 'Star', 'book' => 'Book']),
                Field::text('title')->translatable()->required(),
                Field::link('link'),
            ])->max(12),
        ];
    }

    public function variants(): array
    {
        return ['grid' => 'Grid', 'list' => 'List'];
    }
}
```

The view receives `$data` (values resolved for the current locale), `$variant`, `$classes` (from the style options, see `style_classes`), `$section` and whatever `with()` adds.

Field types: `text` (`email()`, `password()`, `slug('title')` — filled from the title while new, then locked with an edit button), `textarea`, `richText` (sanitized), `number`, `toggle`, `select`, `icon`, `date`, `dateTime`, `color`, `link`, `image`, `file` (media library), `upload` (straight to a disk), `repeater`. Common options: `translatable()`, `required()`, `default()`, `help()`, `rules([...])`, `visibleWhen('field', 'value')`, `readonly()`.

### Changing a section later

Stored content is kept as it was saved. When fields change shape, raise `version()` and convert old data in `upgrade()`; it runs every time old content is read:

```php
public function version(): int { return 2; }

public function upgrade(array $data, int $fromVersion): array
{
    if ($fromVersion < 2) {
        $data['title'] = $data['heading'] ?? null;
        unset($data['heading']);
    }

    return $data;
}
```

Unknown fields and section types are preserved (skipped on the site), so removing code never deletes content.

## Pages

```php
// In a controller: the admin-managed page, rendered with its template.
$page = Content::page('about') ?? abort(404);   // published (or the draft in a signed preview)

return view($page->templateView(), ['page' => $page]);

// Anywhere
$phone = content_global('site.phone');           // a value of a global for the current locale
```

Pages created in the admin are served by slug through a fallback route (`routes.pages`). List the keys your own routes depend on in `protected_pages` so they can't be deleted.

## Collections

```php
class NewsCollection extends CollectionType
{
    public function fields(): array { /* … */ }

    public function driver(): CollectionDriver
    {
        return new EloquentDriver(News::class);   // or new EntriesDriver for the generic table
    }

    public function columns(): array { return ['title' => 'Title', 'status' => 'Status']; }
    public function filters(): array { return ['status' => ['draft' => 'Draft', 'live' => 'Live']]; }
    public function badges(): array { return ['status' => ['draft' => 'warning', 'live' => 'success']]; }
    public function actions(): array
    {
        return [Action::make('archive')->label('Archive')->confirm('Archive it?')->handle(fn (News $news) => $news->archive())];
    }
}
```

Other hooks: `ability()`, `group()`, `canCreate()`, `canDelete($record)`, `navigationBadge()`, `saved($record, $created)`, `rules($recordId)`.

## Dashboard and menu

```php
$this->app->afterResolving(Dashboard::class, fn (Dashboard $dashboard) => $dashboard
    ->add('figures', fn () => ['type' => 'stats', 'items' => [['label' => 'Students', 'value' => Student::count()]]], ability: 'content.manage'));

$this->app->afterResolving(Navigation::class, fn (Navigation $navigation) => $navigation
    ->add('reports', 'Reports', fn () => route('reports.index'), icon: 'chart', ability: 'reports.view'));
```

Widget types: `stats`, `chart` (line), `table`.

## Admin assets

`content.admin.assets`:

- `prebuilt` (default): the package's compiled admin, published to `public/vendor/content`. The project needs no React. Re-publish after each update; `--tag=laravel-assets` (Laravel's default `post-update-cmd`) includes it.
- `vite`: the project's Vite builds the admin, for projects with their own admin pages. Point an alias at the package and add an entry:

```js
// vite.config.js
resolve: { alias: { '@content': fileURLToPath(new URL('./vendor/pishift/content/resources/admin', import.meta.url)) } }
```

```tsx
// resources/admin/app.tsx
import { createContentAdmin } from '@content/app';
createContentAdmin({ pages: import.meta.glob('./pages/**/*.tsx') });   // Inertia::render('App/Reports/Index')
```

## Commands

| Command | What it does |
| --- | --- |
| `content:make-section Name` | Section class and view from the stubs. |
| `content:doctor [--render]` | Checks types (duplicate fields, missing views), templates, stored pages and globals against the current schemas, the published admin assets, and with `--render` requests every published page. Exits non-zero on errors: run it in CI and after every update. |
| `content:upgrade [--dry-run]` | Writes content saved by older type versions back at the current version. |
| `content:types [--path=]` | TypeScript types of what each section, global and collection gives its view. |
| `content:export [--path=database/content] [--except=global:site]` | Writes pages, globals, collection entries and media (with their files) to a folder that ships with the release; `--except` leaves items out. |
| `content:import [--path=database/content] [--force]` | Reads that folder on the server. What the site already has is kept unless `--force`, so it is safe on every deploy. |

## Compatibility rules

The package follows [semantic versioning](https://semver.org). A site pinned to `^1.0` can always take `composer update`.

**Public API** (stable within a major version):

- PHP classes meant to be extended or called: `SectionType`, `GlobalType`, `CollectionType`, `Action`, the `Fields\*` classes, `CollectionDriver` and its drivers, `Content` facade / `ContentManager` public methods, `content_global()`, `Navigation`, `Dashboard`, `Localizer`, the models' public methods.
- Config keys, route names (`content.*`), Blade components, Artisan commands and their options.
- The stored formats: page documents, global data, field values. A new release reads everything an older one wrote.
- React exports used by project admin pages: `createContentAdmin`, `AdminLayout`, the `components/ui` primitives.

**Not public**: anything marked `@internal` (HTTP controllers, middleware, console command classes, caches), Inertia page props, admin page components and CSS classes.

**Rules for maintainers:**

- Patch (`1.2.x`): fixes only.
- Minor (`1.x.0`): new features; nothing public removed or changed in behaviour. Additive migrations only (new tables or nullable columns).
- Deprecate before removing: keep the old API working, add `@deprecated` with the replacement, and log a deprecation notice. Remove only in the next major, listed in `UPGRADE.md`.
- Never change the meaning of stored data; when a stored format must change, bump its version and upgrade on read, like section types do.
- Every release runs the test suite and `content:doctor` against a real project before tagging.

**Updating a site:**

```bash
composer update pishift/content
php artisan migrate
php artisan vendor:publish --tag=content-assets --force
php artisan content:upgrade --dry-run   # then without --dry-run
php artisan content:doctor --render
```

## Releasing

```bash
npm install && npm run build   # rebuilds dist/ (the prebuilt admin)
git add dist && git commit -m "Build admin"
git tag v1.x.y && git push --tags
```

`dist/` is committed in the package repository so Composer installs a ready admin.
