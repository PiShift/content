<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PiShift\Content\Facades\Content;
use PiShift\Content\Models\Entry;
use PiShift\Content\Models\GlobalSet;
use PiShift\Content\Models\Media;
use PiShift\Content\Models\Page;

beforeEach(function () {
    Storage::fake('public');
    $this->folder = 'storage/framework/testing/content-export';
    File::deleteDirectory(base_path($this->folder));

    $page = Page::create(['key' => 'about', 'title' => ['ar' => 'من نحن'], 'slug' => ['ar' => 'about'], 'template' => 'default']);
    Content::saveDraft($page, ['sections' => [['type' => 'hero', 'data' => ['title' => ['ar' => 'عنوان معدل محلياً']]]]]);
    $page->fresh()->publish();

    Content::saveGlobalDraft('site', ['name' => ['ar' => 'الموقع'], 'phone' => '+222 1']);
    GlobalSet::where('key', 'site')->first()->publish();

    Storage::disk('public')->put('content/photo.jpg', 'image-bytes');
    Media::forceCreate(['id' => 7, 'disk' => 'public', 'path' => 'content/photo.jpg', 'name' => 'photo.jpg', 'mime' => 'image/jpeg', 'size' => 11]);
    Entry::create(['collection' => 'faq', 'data' => ['question' => ['ar' => 'سؤال']]]);
});

afterEach(fn () => File::deleteDirectory(base_path($this->folder)));

function aboutTitle(): string
{
    return Page::where('key', 'about')->first()->published['sections'][0]['data']['title']['ar'];
}

it('exports the content and imports it on an install that has none', function () {
    $this->artisan('content:export', ['--path' => $this->folder])->assertSuccessful();

    expect(base_path($this->folder.'/content.json'))->toBeFile()
        ->and(base_path($this->folder.'/media/content/photo.jpg'))->toBeFile();

    Page::query()->delete();
    GlobalSet::query()->delete();
    Entry::query()->delete();
    Media::query()->delete();
    Storage::fake('public');

    $this->artisan('content:import', ['--path' => $this->folder])->assertSuccessful();

    expect(aboutTitle())->toBe('عنوان معدل محلياً')
        ->and(GlobalSet::where('key', 'site')->exists())->toBeTrue()
        ->and(Entry::where('collection', 'faq')->count())->toBe(1)
        ->and(Media::find(7)->path)->toBe('content/photo.jpg');

    Storage::disk('public')->assertExists('content/photo.jpg');
    $this->get('/about')->assertOk()->assertSee('عنوان معدل محلياً');
});

it('keeps what the site already has unless forced', function () {
    $this->artisan('content:export', ['--path' => $this->folder]);

    $page = Page::where('key', 'about')->firstOrFail();
    Content::saveDraft($page, ['sections' => [['type' => 'hero', 'data' => ['title' => ['ar' => 'تعديل على الخادم']]]]]);
    $page->fresh()->publish();

    $this->artisan('content:import', ['--path' => $this->folder])->expectsOutputToContain('page:about')->assertSuccessful();
    expect(aboutTitle())->toBe('تعديل على الخادم');

    $this->artisan('content:import', ['--path' => $this->folder, '--force' => true])->assertSuccessful();
    expect(aboutTitle())->toBe('عنوان معدل محلياً');
});

it('leaves out the items given to --except', function () {
    $this->artisan('content:export', ['--path' => $this->folder, '--except' => ['global:site', 'collection:faq']])->assertSuccessful();

    $snapshot = json_decode(File::get(base_path($this->folder.'/content.json')), true);

    expect(collect($snapshot['globals'])->pluck('key'))->not->toContain('site')
        ->and($snapshot['entries'])->toBe([]);
});

it('does nothing when there is no export', function () {
    $this->artisan('content:import', ['--path' => $this->folder])->expectsOutputToContain('Nothing to import')->assertSuccessful();
});
