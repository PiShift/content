<?php

use Inertia\Testing\AssertableInertia as Assert;
use PiShift\Content\Support\AdminBrand;

it('keeps the package look when no brand is configured', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertDontSee('--color-accent:', false)
        ->assertInertia(fn (Assert $page) => $page->where('admin.logo', null));
});

it('prints the accent colour with its derived shades and a readable text colour', function () {
    config(['content.admin.brand.accent' => '#0A1F5C']);

    $this->get('/admin/login')
        ->assertSee('--color-accent:#0a1f5c;', false)
        ->assertSee('--color-accent-strong:color-mix(in oklab, #0a1f5c 80%, black);', false)
        ->assertSee('--color-accent-soft:color-mix(in oklab, #0a1f5c 12%, white);', false)
        ->assertSee('--color-on-accent:#ffffff;', false);

    config(['content.admin.brand.accent' => '#f4c430']);
    expect(app(AdminBrand::class)->css())->toContain('--color-on-accent:#17211b;');
});

it('ignores a colour that is not #rrggbb, so nothing can be injected into the page', function (mixed $accent) {
    config(['content.admin.brand.accent' => $accent]);

    expect(app(AdminBrand::class)->css())->toBe('');
    $this->get('/admin/login')->assertDontSee('display:none', false)->assertDontSee('--color-accent:', false);
})->with([
    'a name' => ['red'],
    'short hex' => ['#fff'],
    'CSS after the colour' => ['#0a1f5c;}body{display:none'],
    'an array' => [['#0a1f5c']],
]);

it('shares the logo URL with the admin pages', function () {
    config(['content.admin.brand.logo' => '/images/logo.png']);
    $this->get('/admin/login')->assertInertia(fn (Assert $page) => $page->where('admin.logo', asset('images/logo.png')));

    config(['content.admin.brand.logo' => 'https://cdn.example.com/logo.svg']);
    $this->get('/admin/login')->assertInertia(fn (Assert $page) => $page->where('admin.logo', 'https://cdn.example.com/logo.svg'));
});
