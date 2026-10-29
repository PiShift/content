<?php

use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use PiShift\Content\Facades\Content;
use PiShift\Content\Models\Page;
use PiShift\Content\Tests\Fixtures\User;

/**
 * @return array<string, mixed>
 */
function adminDocument(string $frTitle = 'Titre'): array
{
    return ['sections' => [[
        'id' => 'section-1',
        'type' => 'cards',
        'variant' => 'grid-3',
        'data' => [
            'title' => ['ar' => 'عنوان', 'fr' => $frTitle],
            'cards' => [['_id' => 'card-1', 'title' => ['ar' => 'بطاقة', 'fr' => 'Carte']]],
        ],
    ]]];
}

function publishedDemoPage(): Page
{
    $page = Page::create(['key' => 'demo', 'title' => ['ar' => 'تجربة', 'fr' => 'Démo'], 'slug' => ['ar' => 'demo', 'fr' => 'demo', 'en' => 'demo'], 'template' => 'default']);
    Content::saveDraft($page, adminDocument());

    return $page->fresh()->publish();
}

/**
 * @return array<string, mixed>
 */
function updatePayload(array $overrides = []): array
{
    return [
        'title' => ['ar' => 'تجربة', 'fr' => 'Démo'],
        'slug' => 'demo',
        'template' => 'default',
        'seo' => ['title' => ['fr' => 'SEO démo']],
        'document' => adminDocument('Titre modifié'),
        ...$overrides,
    ];
}

it('sends guests to the admin login page', function () {
    $this->get('/admin/pages')->assertRedirect('/admin/login');

    $this->get('/admin/login')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Auth/Login')->has('t.login')->where('admin.dir', 'rtl'));
});

it('logs in with valid credentials and rejects wrong ones', function () {
    $user = User::factory()->create(['password' => 'secret-password']);

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'secret-password'])->assertRedirect(route('content.admin.home'));
    $this->assertAuthenticatedAs($user);

    $this->post('/admin/logout')->assertRedirect('/admin/login');
    $this->assertGuest();
});

it('forbids users without the content.manage ability', function () {
    Gate::define('content.manage', fn () => false);

    $this->actingAs(User::factory()->create())->get('/admin/pages')->assertForbidden();
});

it('lists pages with their status', function () {
    publishedDemoPage();

    $this->actingAs(User::factory()->create())
        ->get('/admin/pages')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Pages/Index')
            ->has('pages', 1)
            ->where('pages.0.key', 'demo')
            ->where('pages.0.status', 'published')
            ->where('pages.0.path', '/demo')
            ->where('navigation.0.key', 'dashboard')
            ->where('navigation.1.key', 'pages'));
});

it('creates a page and opens its editor', function () {
    $this->actingAs(User::factory()->create())
        ->post('/admin/pages', ['title' => ['ar' => 'من نحن', 'fr' => 'À propos'], 'slug' => 'a-propos', 'template' => 'default'])
        ->assertRedirect();

    $page = Page::where('key', 'a-propos')->firstOrFail();

    expect($page->slug)->toBe(['ar' => 'a-propos', 'fr' => 'a-propos', 'en' => 'a-propos'])
        ->and($page->isPublished())->toBeFalse();

    $this->get("/admin/pages/{$page->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('Pages/Edit')
            ->where('page.key', 'a-propos')
            ->has('document.sections', 0)
            ->has('sectionTypes')
            ->where('can.publish', true));
});

it('rejects a slug already used by another page', function () {
    publishedDemoPage();

    $this->actingAs(User::factory()->create())
        ->post('/admin/pages', ['title' => ['ar' => 'ثانية'], 'slug' => 'demo', 'template' => 'default'])
        ->assertSessionHasErrors('slug');
});

it('keeps the public page unchanged when saving a draft and updates it on publish', function () {
    $page = publishedDemoPage();
    $user = User::factory()->create();

    $this->actingAs($user)->put("/admin/pages/{$page->id}", updatePayload())->assertRedirect()->assertSessionHasNoErrors();

    expect($page->fresh()->hasUnpublishedChanges())->toBeTrue();
    $this->withSession(['locale' => 'fr'])->get('/demo')->assertSee('Titre')->assertDontSee('Titre modifié');

    $this->actingAs($user)->put("/admin/pages/{$page->id}", updatePayload(['publish' => true]))->assertSessionHasNoErrors();

    $this->withSession(['locale' => 'fr'])->get('/demo')->assertSee('Titre modifié')->assertSee('<title>SEO démo</title>', false);
    expect($page->fresh()->revisions()->count())->toBe(2);
});

it('returns field errors keyed by their path in the document', function () {
    $page = publishedDemoPage();
    $document = adminDocument();
    $document['sections'][0]['data']['cards'][0]['title']['ar'] = '';

    $this->actingAs(User::factory()->create())
        ->put("/admin/pages/{$page->id}", updatePayload(['document' => $document]))
        ->assertSessionHasErrors('sections.0.data.cards.0.title.ar');
});

it('lets editors save drafts but not publish without content.publish', function () {
    Gate::define('content.publish', fn () => false);
    $page = publishedDemoPage();

    $this->actingAs(User::factory()->create())->put("/admin/pages/{$page->id}", updatePayload())->assertSessionHasNoErrors();
    $this->put("/admin/pages/{$page->id}", updatePayload(['publish' => true]))->assertForbidden();

    $this->get("/admin/pages/{$page->id}")->assertInertia(fn (Assert $inertia) => $inertia->where('can.publish', false));
});

it('deletes pages except the protected ones', function () {
    $page = publishedDemoPage();
    $user = User::factory()->create();

    config(['content.protected_pages' => ['demo']]);
    $this->actingAs($user)->delete("/admin/pages/{$page->id}")->assertForbidden();

    config(['content.protected_pages' => []]);
    $this->delete("/admin/pages/{$page->id}")->assertRedirect(route('content.admin.pages.index'));

    expect(Page::count())->toBe(0);
});

it('keeps the admin language separate from the public site language', function () {
    $this->actingAs(User::factory()->create())
        ->withSession(['locale' => 'ar'])
        ->get('/admin/locale/fr')
        ->assertRedirect();

    $this->get('/admin/pages')->assertInertia(fn (Assert $page) => $page->where('admin.locale', 'fr')->where('admin.dir', 'ltr')->where('t.pages', 'Pages'));
    expect(session('locale'))->toBe('ar');
});
