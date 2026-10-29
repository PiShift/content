<?php

use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use PiShift\Content\Document\PageDocument;
use PiShift\Content\Facades\Content;
use PiShift\Content\Models\GlobalSet;
use PiShift\Content\Models\Page;
use PiShift\Content\Sections\SectionRegistry;
use PiShift\Content\Support\Localizer;
use PiShift\Content\Tests\Fixtures\User;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function cardsSection(array $overrides = []): array
{
    return array_replace_recursive([
        'type' => 'cards',
        'variant' => 'list',
        'style' => ['background' => 'muted'],
        'data' => [
            'title' => ['ar' => 'عنوان', 'fr' => 'Titre'],
            'intro' => ['ar' => '<p>مقدمة</p>', 'fr' => '<p>Intro <script>alert(1)</script><a href="javascript:alert(1)" onclick="x()">lien</a></p>'],
            'cards' => [
                ['title' => ['ar' => 'بطاقة', 'fr' => 'Carte un'], 'link' => ['url' => '/about', 'label' => ['ar' => 'المزيد', 'fr' => 'En savoir plus']]],
            ],
        ],
    ], $overrides);
}

function demoPage(array $sections, bool $publish = true): Page
{
    $page = Page::create(['key' => 'demo', 'title' => ['ar' => 'تجربة', 'fr' => 'Démo'], 'slug' => ['ar' => 'demo', 'fr' => 'demo'], 'template' => 'default']);

    Content::saveDraft($page, ['sections' => $sections]);

    return $publish ? $page->fresh()->publish() : $page->fresh();
}

function visitPageIn(string $locale, string $uri): TestResponse
{
    return test()->withSession(['locale' => $locale])->get($uri);
}

it('validates required translatable fields and repeater limits', function (array $override, string $errorKey) {
    $page = Page::create(['key' => 'demo', 'template' => 'default']);

    try {
        Content::saveDraft($page, ['sections' => [cardsSection($override)]]);
        $this->fail('Validation should fail.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey($errorKey);
    }
})->with([
    'title missing in the default locale' => [['data' => ['title' => ['ar' => '']]], 'sections.0.data.title.ar'],
    'card title missing' => [['data' => ['cards' => [['title' => ['ar' => '']]]]], 'sections.0.data.cards.0.title.ar'],
    'too many cards' => [['data' => ['cards' => array_fill(0, 4, ['title' => ['ar' => 'x']])]], 'sections.0.data.cards'],
    'unknown style value' => [['style' => ['background' => 'neon']], 'sections.0.style.background'],
]);

it('accepts a valid section and gives sections and cards stable ids', function () {
    $page = demoPage([cardsSection()], publish: false);
    $section = $page->draft['sections'][0];

    expect($section['id'])->toBeString()->not->toBeEmpty()
        ->and($section['version'])->toBe(1)
        ->and($section['data']['cards'][0]['_id'])->toBeString()->not->toBeEmpty();

    Content::saveDraft($page, $page->draft);

    expect($page->fresh()->draft['sections'][0]['id'])->toBe($section['id'])
        ->and($page->fresh()->draft['sections'][0]['data']['cards'][0]['_id'])->toBe($section['data']['cards'][0]['_id']);
});

it('resolves translatable values with the configured fallbacks', function () {
    $localizer = Localizer::fromConfig();
    $value = ['ar' => 'عربي', 'fr' => '', 'en' => null];

    expect($localizer->forLocale('ar')->pick($value))->toBe('عربي')
        ->and($localizer->forLocale('fr')->pick($value))->toBe('عربي')
        ->and($localizer->forLocale('en')->pick(['ar' => 'عربي', 'fr' => 'Français']))->toBe('Français')
        ->and($localizer->forLocale('fr')->pick('plain'))->toBe('plain');
});

it('renders published sections in the visitor locale with style classes and variant', function () {
    demoPage([cardsSection()]);

    visitPageIn('fr', '/demo')
        ->assertOk()
        ->assertSee('<title>Démo</title>', false)
        ->assertSee('Titre')
        ->assertSee('Carte un')
        ->assertSee('href="/about"', false)
        ->assertSee('En savoir plus')
        ->assertSee('bg-muted')
        ->assertSee('data-variant="list"', false);

    visitPageIn('ar', '/demo')->assertSee('عنوان')->assertSee('بطاقة');
});

it('cleans rich text', function () {
    demoPage([cardsSection()]);

    visitPageIn('fr', '/demo')
        ->assertDontSee('<script>', false)
        ->assertDontSee('javascript:', false)
        ->assertDontSee('onclick', false)
        ->assertSee('lien');
});

it('keeps unknown sections and fields, and skips unknown sections when rendering', function () {
    $page = demoPage([
        cardsSection(['data' => ['legacy_note' => 'keep me']]),
        ['type' => 'testimonials', 'data' => ['quote' => 'Future section']],
    ]);

    $stored = $page->fresh()->published['sections'];

    expect($stored)->toHaveCount(2)
        ->and($stored[0]['data']['legacy_note'])->toBe('keep me')
        ->and($stored[1]['type'])->toBe('testimonials')
        ->and($stored[1]['data']['quote'])->toBe('Future section');

    visitPageIn('fr', '/demo')->assertOk()->assertSee('Titre')->assertDontSee('Future section');
});

it('upgrades content saved by an older section version when reading it', function () {
    $page = Page::create([
        'key' => 'demo',
        'slug' => ['ar' => 'demo'],
        'template' => 'default',
        'published' => ['version' => 1, 'sections' => [['id' => 'abc', 'type' => 'hero', 'version' => 1, 'data' => ['heading' => ['ar' => 'عنوان قديم', 'fr' => 'Ancien titre']]]]],
        'published_at' => now(),
    ]);

    $section = Content::resolve($page)->sections()[0];

    expect($section->data['title'])->toBe('عنوان قديم');

    $normalized = $page->publishedDocument()->normalize(app(SectionRegistry::class))->toArray();

    expect($normalized['sections'][0]['version'])->toBe(2)
        ->and($normalized['sections'][0]['data'])->not->toHaveKey('heading');
});

it('falls back to the default variant when the stored one no longer exists', function () {
    $page = demoPage([cardsSection(['variant' => 'carousel'])]);

    expect($page->fresh()->published['sections'][0]['variant'])->toBe('grid-3');
});

it('hides hidden sections', function () {
    demoPage([cardsSection(['hidden' => true]), ['type' => 'hero', 'data' => ['title' => ['ar' => 'بطل', 'fr' => 'Héros']]]]);

    visitPageIn('fr', '/demo')->assertSee('Héros')->assertDontSee('Carte un');
});

it('shows drafts only through a signed preview url', function () {
    $page = demoPage([cardsSection()]);

    Content::saveDraft($page, ['sections' => [cardsSection(['data' => ['title' => ['fr' => 'Brouillon']]])]]);

    visitPageIn('fr', '/demo')->assertSee('Titre')->assertDontSee('Brouillon');

    $this->get(Content::previewUrl($page->fresh(), 'fr'))
        ->assertOk()
        ->assertSee('Brouillon')
        ->assertSee('data-content-section=', false);

    $this->get(route('content.preview', $page))->assertForbidden();

    $page->fresh()->publish();

    visitPageIn('fr', '/demo')->assertSee('Brouillon');
});

it('does not serve unpublished pages and returns 404 for unknown paths', function () {
    demoPage([cardsSection()], publish: false);

    $this->get('/demo')->assertNotFound();
    $this->get('/nothing-here')->assertNotFound();
});

it('keeps a revision on publish and restores it into the draft', function () {
    $user = User::factory()->create();
    $page = demoPage([cardsSection()]);

    Content::saveDraft($page, ['sections' => [cardsSection(['data' => ['title' => ['ar' => 'ثاني', 'fr' => 'Deuxième']]])]]);
    $page->fresh()->publish($user->id);

    expect($page->revisions()->count())->toBe(2)
        ->and($page->revisions()->first()->user_id)->toBe($user->id);

    $first = $page->revisions()->reorder('id')->first();
    $page->fresh()->restore($first);

    expect(PageDocument::fromArray($page->fresh()->draft)->sections()[0]['data']['title']['fr'])->toBe('Titre');
});

it('saves, publishes and reads globals with the current locale', function () {
    Content::saveGlobalDraft('site', ['name' => ['ar' => 'المعهد', 'fr' => 'Institut'], 'phone' => '+222 1']);

    expect(content_global('site.phone'))->toBeNull();

    GlobalSet::where('key', 'site')->first()->publish();

    app()->setLocale('fr');
    expect(content_global('site.name'))->toBe('Institut')
        ->and(content_global('site.phone'))->toBe('+222 1')
        ->and(content_global('site.missing', 'default'))->toBe('default');

    Content::saveGlobalDraft('site', ['name' => ['ar' => 'المعهد', 'fr' => 'Institut 2'], 'phone' => '+222 2']);
    GlobalSet::where('key', 'site')->first()->publish();

    expect(content_global('site.name'))->toBe('Institut 2');
});

it('rejects an invalid global', function () {
    Content::saveGlobalDraft('site', ['name' => ['fr' => 'Sans arabe']]);
})->throws(ValidationException::class);

it('describes section types for the admin', function () {
    $schema = collect(Content::sections()->toSchema())->firstWhere('key', 'cards');

    expect($schema['variants'])->toBe(['grid-3' => '3 columns', 'list' => 'List'])
        ->and(collect($schema['fields'])->firstWhere('name', 'cards'))
        ->type->toBe('repeater')
        ->max->toBe(3)
        ->itemLabel->toBe('title');
});
