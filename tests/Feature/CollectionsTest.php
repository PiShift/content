<?php

use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use PiShift\Content\Collections\CollectionRegistry;
use PiShift\Content\Models\Entry;
use PiShift\Content\Tests\Fixtures\Article;
use PiShift\Content\Tests\Fixtures\ArticlesCollection;
use PiShift\Content\Tests\Fixtures\FaqCollection;
use PiShift\Content\Tests\Fixtures\User;

beforeEach(function () {
    Article::createTable();
    app(CollectionRegistry::class)->register(FaqCollection::class)->register(ArticlesCollection::class);
    $this->actingAs(User::factory()->create());
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function articleData(array $overrides = []): array
{
    return array_replace(['title' => ['ar' => 'مقال', 'fr' => 'Article'], 'slug' => 'premier-article', 'kind' => 'news', 'status' => 'draft'], $overrides);
}

describe('entries driver', function () {
    it('stores records without a table of their own, then filters, searches and deletes them', function () {
        $this->post('/admin/collections/faq', ['data' => ['question' => ['ar' => 'سؤال', 'fr' => 'Comment s’inscrire ?'], 'answer' => ['fr' => 'En ligne.'], 'topic' => 'admission']])->assertSessionHasNoErrors();
        $this->post('/admin/collections/faq', ['data' => ['question' => ['ar' => 'امتحان', 'fr' => 'Quand sont les examens ?'], 'topic' => 'exams']])->assertSessionHasNoErrors();

        $entry = Entry::where('data->topic', 'admission')->firstOrFail();
        expect($entry->collection)->toBe('faq')->and($entry->data['question']['fr'])->toBe('Comment s’inscrire ?');

        $this->get('/admin/collections/faq')->assertInertia(fn (Assert $page) => $page->component('Collections/Index')->has('records.data', 2));
        $this->get('/admin/collections/faq?filters[topic]=exams')->assertInertia(fn (Assert $page) => $page->has('records.data', 1));
        $this->get('/admin/collections/faq?search=examens')->assertInertia(fn (Assert $page) => $page->has('records.data', 1));

        $this->put("/admin/collections/faq/{$entry->id}", ['data' => ['question' => ['ar' => 'سؤال', 'fr' => 'Inscription ?'], 'topic' => 'admission']])->assertSessionHasNoErrors();
        expect($entry->fresh()->data['question']['fr'])->toBe('Inscription ?');

        $this->delete("/admin/collections/faq/{$entry->id}")->assertRedirect('/admin/collections/faq');
        expect(Entry::count())->toBe(1);
    });

    it('validates records against the collection fields', function () {
        $this->post('/admin/collections/faq', ['data' => ['question' => ['fr' => 'Sans arabe'], 'topic' => 'other']])
            ->assertSessionHasErrors(['data.question.ar', 'data.topic']);
    });
});

describe('eloquent driver', function () {
    it('creates, updates and deletes records through the project model', function () {
        $this->post('/admin/collections/articles', ['data' => articleData(['views' => 999])])->assertSessionHasNoErrors();

        $article = Article::firstOrFail();
        expect($article->title)->toBe(['ar' => 'مقال', 'fr' => 'Article'])
            ->and($article->views)->toBe(0);

        $this->get("/admin/collections/articles/{$article->id}")->assertInertia(fn (Assert $page) => $page
            ->component('Collections/Form')
            ->where('record.data.slug', 'premier-article')
            ->where('collection.fields.1.slugFrom', 'title'));

        $this->put("/admin/collections/articles/{$article->id}", ['data' => articleData(['slug' => 'article-modifie'])])->assertSessionHasNoErrors();
        expect($article->fresh()->slug)->toBe('article-modifie');

        $this->delete("/admin/collections/articles/{$article->id}")->assertRedirect();
        expect(Article::count())->toBe(0);
    });

    it('applies the collection rules and conditional fields', function () {
        Article::create(['title' => ['ar' => 'موجود'], 'slug' => 'existe']);

        $this->post('/admin/collections/articles', ['data' => articleData(['slug' => 'existe'])])->assertSessionHasErrors('data.slug');
        $this->post('/admin/collections/articles', ['data' => articleData(['slug' => 'Pas Valide'])])->assertSessionHasErrors('data.slug');

        $this->post('/admin/collections/articles', ['data' => articleData(['slug' => 'appel', 'kind' => 'tender'])])->assertSessionHasErrors('data.deadline');
        $this->post('/admin/collections/articles', ['data' => articleData(['slug' => 'appel', 'kind' => 'tender', 'deadline' => '2026-12-01'])])->assertSessionHasNoErrors();
        $this->post('/admin/collections/articles', ['data' => articleData(['slug' => 'actualite'])])->assertSessionHasNoErrors();

        expect(Article::where('slug', 'appel')->first()->deadline->format('Y-m-d'))->toBe('2026-12-01');
    });

    it('lists records with badges, filters and the menu count', function () {
        Article::create(['title' => ['ar' => 'أ'], 'slug' => 'brouillon']);
        Article::create(['title' => ['ar' => 'ب'], 'slug' => 'appel-offres', 'kind' => 'tender', 'status' => 'published']);

        $this->get('/admin/collections/articles')->assertInertia(fn (Assert $page) => $page
            ->has('records.data', 2)
            ->where('collection.badges.status.published', 'success')
            ->where('navigation', fn ($items) => collect($items)->firstWhere('key', 'collection.articles')['badge'] === 1));

        $this->get('/admin/collections/articles?filters[kind]=tender')->assertInertia(fn (Assert $page) => $page->has('records.data', 1));
        $this->get('/admin/collections/articles?search=brouillon')->assertInertia(fn (Assert $page) => $page->has('records.data', 1));
    });

    it('runs record actions only where they are visible', function () {
        $article = Article::create(['title' => ['ar' => 'أ'], 'slug' => 'brouillon']);

        $this->post("/admin/collections/articles/{$article->id}/actions/publish")->assertRedirect()->assertSessionHas('content.success', 'Published.');
        expect($article->fresh()->status)->toBe('published');

        $this->post("/admin/collections/articles/{$article->id}/actions/publish")->assertForbidden();
        $this->post("/admin/collections/articles/{$article->id}/actions/unknown")->assertNotFound();
    });
});

it('refuses users without the collection ability and unknown collections', function () {
    $this->get('/admin/collections/unknown')->assertNotFound();

    Gate::define('content.manage', fn () => false);
    $this->get('/admin/collections/articles')->assertForbidden();
});
