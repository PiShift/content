<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use PiShift\Content\Facades\Content;
use PiShift\Content\Fields\Field;
use PiShift\Content\Models\Page;
use PiShift\Content\Sections\SectionType;
use PiShift\Content\Tests\Fixtures\User;

/**
 * A page stored as-is (no normalizing or validation), like content saved by an older version.
 *
 * @param  list<array<string, mixed>>  $sections
 */
function storedPage(array $sections, string $template = 'default'): Page
{
    return Page::create([
        'key' => 'demo',
        'title' => ['ar' => 'تجربة', 'fr' => 'Démo'],
        'slug' => ['ar' => 'demo', 'fr' => 'demo'],
        'template' => $template,
        'status' => 'published',
        'published' => ['version' => 1, 'sections' => $sections],
        'published_at' => now(),
    ]);
}

describe('content:doctor', function () {
    it('reports healthy content', function () {
        storedPage([['id' => 'a', 'type' => 'hero', 'version' => 2, 'data' => ['title' => ['ar' => 'مرحبا', 'fr' => 'Bonjour']]]]);

        $this->artisan('content:doctor', ['--render' => true])
            ->expectsOutputToContain('Content is healthy')
            ->assertSuccessful();
    });

    it('fails when stored content no longer validates', function () {
        storedPage([['id' => 'a', 'type' => 'hero', 'version' => 2, 'data' => ['title' => ['fr' => 'Sans arabe']]]]);

        $this->artisan('content:doctor')
            ->expectsOutputToContain('page demo (published)')
            ->assertFailed();
    });

    it('warns about stored sections whose type was removed', function () {
        storedPage([['id' => 'a', 'type' => 'carousel', 'data' => []]]);

        $this->artisan('content:doctor')
            ->expectsOutputToContain('unknown type [carousel]')
            ->assertSuccessful();
    });

    it('fails when a section type has no view or a page uses an unknown template', function () {
        Content::sections()->register(new class extends SectionType
        {
            public function key(): string
            {
                return 'ghost';
            }

            public function fields(): array
            {
                return [Field::text('title'), Field::text('title')];
            }
        });
        storedPage([], 'landing');

        $this->artisan('content:doctor')
            ->expectsOutputToContain('View [content.sections.ghost] is missing.')
            ->expectsOutputToContain('Field [title] is defined twice.')
            ->expectsOutputToContain('Template [landing] is not configured.')
            ->assertFailed();
    });
});

describe('content:upgrade', function () {
    it('rewrites content saved by an older section version', function () {
        $page = storedPage([['id' => 'a', 'type' => 'hero', 'version' => 1, 'data' => ['heading' => ['ar' => 'قديم', 'fr' => 'Ancien']]]]);

        $this->artisan('content:upgrade', ['--dry-run' => true])->expectsOutputToContain('would be upgraded')->assertSuccessful();
        expect($page->fresh()->published['sections'][0]['version'])->toBe(1);

        $this->artisan('content:upgrade')->expectsOutputToContain('1 document(s) upgraded')->assertSuccessful();

        expect($page->fresh()->published['sections'][0])
            ->version->toBe(2)
            ->data->toMatchArray(['title' => ['ar' => 'قديم', 'fr' => 'Ancien']])
            ->data->not->toHaveKey('heading');

        $this->artisan('content:upgrade')->expectsOutputToContain('already at the current type versions')->assertSuccessful();
    });
});

it('generates TypeScript types from the schemas', function () {
    $path = storage_path('framework/testing/content.d.ts');

    $this->artisan('content:types', ['--path' => $path])->assertSuccessful();

    expect(file_get_contents($path))
        ->toContain('export interface HeroSection {')
        ->toContain('    title: string;')
        ->toContain('    background: ContentImage | null;')
        ->toContain("    'hero': HeroSection;")
        ->toContain('export interface SiteGlobal {');

    unlink($path);
});

describe('prebuilt admin', function () {
    // A scratch public folder, so the project's real published admin is left alone.
    beforeEach(fn () => $this->app->usePublicPath(storage_path('framework/testing/public')));
    afterEach(fn () => File::deleteDirectory(storage_path('framework/testing/public')));

    it('loads the published package build instead of the project Vite build', function () {
        File::ensureDirectoryExists(public_path('vendor/content'));
        File::put(public_path('vendor/content/manifest.json'), json_encode([
            'resources/admin/standalone.tsx' => ['file' => 'assets/standalone-abc.js', 'src' => 'resources/admin/standalone.tsx', 'isEntry' => true, 'css' => ['assets/standalone-abc.css']],
        ]));
        config(['content.admin.assets' => 'prebuilt']);

        $this->withVite()->get('/admin/login')
            ->assertOk()
            ->assertSee('vendor/content/assets/standalone-abc.js', false)
            ->assertSee('vendor/content/assets/standalone-abc.css', false);
    });

    it('tells doctor when the prebuilt assets are not published', function () {
        config(['content.admin.assets' => 'prebuilt']);

        $this->artisan('content:doctor')
            ->expectsOutputToContain('vendor:publish --tag=content-assets')
            ->assertFailed();
    });
});

describe('content:user', function () {
    it('creates the first admin user, who can then sign in', function () {
        $this->artisan('content:user', ['email' => 'admin@example.com'])
            ->expectsQuestion('Password (at least 8 characters)', 'long-enough-password')
            ->expectsOutputToContain('User admin@example.com created.')
            ->assertSuccessful();

        $user = User::where('email', 'admin@example.com')->firstOrFail();
        expect($user->name)->toBe('admin');

        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'long-enough-password'])->assertRedirect(route('content.admin.home'));
        $this->assertAuthenticatedAs($user);
    });

    it('sets the password of an existing user and keeps their name', function () {
        $user = User::factory()->create(['email' => 'editor@example.com', 'name' => 'Mariem']);

        $this->artisan('content:user', ['email' => 'editor@example.com', '--password' => 'new-password-123'])
            ->expectsOutputToContain('Password of editor@example.com updated.')
            ->assertSuccessful();

        expect($user->fresh()->name)->toBe('Mariem')
            ->and(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue()
            ->and(User::count())->toBe(1);
    });

    it('refuses an invalid email or a short password', function () {
        $this->artisan('content:user', ['email' => 'not-an-email', '--password' => 'short'])->assertFailed();

        expect(User::count())->toBe(0);
    });
});
