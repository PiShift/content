<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PiShift\Content\Facades\Content;
use PiShift\Content\Models\GlobalSet;
use PiShift\Content\Models\Media;
use PiShift\Content\Models\Page;
use PiShift\Content\Tests\Fixtures\User;

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
});

it('uploads files to the media library and returns them as JSON for the picker', function () {
    $response = $this->postJson('/admin/media', ['file' => UploadedFile::fake()->image('logo.png', 640, 480)])
        ->assertCreated()
        ->assertJsonPath('name', 'logo.png')
        ->assertJsonPath('width', 640)
        ->assertJsonPath('is_image', true);

    $media = Media::findOrFail($response->json('id'));
    Storage::disk('public')->assertExists($media->path);

    $this->getJson('/admin/media/list?type=image')->assertOk()->assertJsonPath('data.0.id', $media->id);
    $this->getJson("/admin/media/{$media->id}")->assertOk()->assertJsonPath('url', $media->url);
});

it('rejects disallowed file types and oversized files', function (UploadedFile $file) {
    $this->postJson('/admin/media', ['file' => $file])->assertUnprocessable()->assertJsonValidationErrors('file');

    expect(Media::count())->toBe(0);
})->with([
    'script' => fn () => UploadedFile::fake()->create('evil.php', 1, 'text/x-php'),
    'svg' => fn () => UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml'),
    'too large' => fn () => UploadedFile::fake()->create('big.pdf', 30000, 'application/pdf'),
]);

it('updates alt text and deletes media with its file', function () {
    $id = $this->postJson('/admin/media', ['file' => UploadedFile::fake()->image('photo.jpg')])->json('id');
    $media = Media::findOrFail($id);

    $this->put("/admin/media/{$id}", ['name' => 'Photo du directeur', 'alt' => ['ar' => 'صورة المدير', 'fr' => 'Le directeur']])->assertRedirect();
    expect($media->fresh()->alt)->toBe(['ar' => 'صورة المدير', 'fr' => 'Le directeur']);

    $this->delete("/admin/media/{$id}")->assertRedirect();
    Storage::disk('public')->assertMissing($media->path);
    expect(Media::count())->toBe(0);
});

it('renders an image field from the media library with its translated alt text', function () {
    $id = $this->postJson('/admin/media', ['file' => UploadedFile::fake()->image('hero.jpg')])->json('id');
    Media::find($id)->update(['alt' => ['ar' => 'بديل', 'fr' => 'Alternative']]);

    $page = Page::create(['key' => 'demo', 'slug' => ['ar' => 'demo', 'fr' => 'demo'], 'template' => 'default']);
    Content::saveDraft($page, ['sections' => [['type' => 'hero', 'data' => ['title' => ['ar' => 'عنوان'], 'background' => $id]]]]);
    $page->fresh()->publish();

    $this->get('/demo')->assertSee(Media::find($id)->url);
    expect(Content::resolve($page->fresh())->sections()[0]->data['background']['url'])->toBe(Media::find($id)->url);
});

it('edits, saves and publishes a global from the admin', function () {
    $this->get('/admin/globals')->assertInertia(fn (Assert $page) => $page->component('Globals/Index')->where('globals', fn ($globals) => collect($globals)->pluck('key')->contains('site')));

    $this->get('/admin/globals/site')->assertInertia(fn (Assert $page) => $page->component('Globals/Edit')->has('fields', 2));

    $this->put('/admin/globals/site', ['data' => ['name' => ['ar' => 'المعهد', 'fr' => 'Institut'], 'phone' => '+222 3']])->assertSessionHasNoErrors();
    expect(content_global('site.phone'))->toBeNull();

    $this->put('/admin/globals/site', ['data' => ['name' => ['ar' => 'المعهد', 'fr' => 'Institut'], 'phone' => '+222 3'], 'publish' => true])->assertSessionHasNoErrors();
    expect(content_global('site.phone'))->toBe('+222 3')
        ->and(GlobalSet::where('key', 'site')->first()->revisions()->count())->toBe(1);

    $this->put('/admin/globals/site', ['data' => ['name' => ['fr' => 'Sans arabe']]])->assertSessionHasErrors('data.name.ar');
    $this->get('/admin/globals/unknown')->assertNotFound();
});

it('lists revisions and restores one into the draft', function () {
    $page = Page::create(['key' => 'demo', 'title' => ['ar' => 'تجربة'], 'slug' => ['ar' => 'demo'], 'template' => 'default']);
    Content::saveDraft($page, ['sections' => [['type' => 'hero', 'data' => ['title' => ['ar' => 'الأولى']]]]]);
    $page->fresh()->publish();
    Content::saveDraft($page->fresh(), ['sections' => [['type' => 'hero', 'data' => ['title' => ['ar' => 'الثانية']]]]]);
    $page->fresh()->publish();

    $revisions = $this->getJson("/admin/pages/{$page->id}/revisions")->assertOk()->assertJsonCount(2)->json();
    $first = collect($revisions)->sortBy('id')->first();

    $this->post("/admin/pages/{$page->id}/revisions/{$first['id']}/restore")->assertRedirect(route('content.admin.pages.edit', $page));

    expect($page->fresh()->draft['sections'][0]['data']['title']['ar'])->toBe('الأولى')
        ->and($page->fresh()->published['sections'][0]['data']['title']['ar'])->toBe('الثانية');

    $other = Page::create(['key' => 'other', 'template' => 'default']);
    $this->post("/admin/pages/{$other->id}/revisions/{$first['id']}/restore")->assertNotFound();
});

it('renders the unsaved document for the live preview with section markers', function () {
    $page = Page::create(['key' => 'demo', 'title' => ['ar' => 'تجربة'], 'slug' => ['ar' => 'demo'], 'template' => 'default']);

    $this->post("/admin/pages/{$page->id}/preview", [
        'locale' => 'fr',
        'document' => ['sections' => [['id' => 'abc', 'type' => 'hero', 'data' => ['title' => ['ar' => 'غير محفوظ', 'fr' => 'Pas encore enregistré']]]]],
    ])
        ->assertOk()
        ->assertSee('Pas encore enregistré')
        ->assertSee('data-content-section="abc"', false)
        ->assertSee('content:select', false)
        ->assertSee('<base href=', false);

    expect($page->fresh()->draft)->toBeNull();
});

it('shows the media and globals entries in the admin menu', function () {
    $this->get('/admin/pages')->assertInertia(fn (Assert $page) => $page
        ->where('navigation.0.key', 'dashboard')
        ->where('navigation.1.key', 'pages')
        ->where('navigation.2.key', 'globals')
        ->where('navigation.3.key', 'media'));
});
