<?php

namespace PiShift\Content\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\View;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\TestCase as Orchestra;
use PiShift\Content\ContentServiceProvider;
use PiShift\Content\Facades\Content;
use PiShift\Content\Tests\Fixtures\CardsSection;
use PiShift\Content\Tests\Fixtures\HeroSection;
use PiShift\Content\Tests\Fixtures\SessionLocale;
use PiShift\Content\Tests\Fixtures\Site;
use PiShift\Content\Tests\Fixtures\User;

/**
 * A bare Laravel app with the package, two section types (cards, hero), a "site" global and a
 * test layout. Nothing is discovered from a project: the fixtures are registered by hand.
 */
abstract class TestCase extends Orchestra
{
    use WithLaravelMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        View::addLocation(__DIR__.'/Fixtures/views');
        $this->app->make(Kernel::class)->appendMiddlewareToGroup('web', SessionLocale::class);

        Content::sections()->register(CardsSection::class)->register(HeroSection::class);
        Content::globals()->register(Site::class);
    }

    protected function getPackageProviders($app): array
    {
        return [InertiaServiceProvider::class, ContentServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        tap($app['config'], function (Repository $config): void {
            $config->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
            $config->set('app.locale', 'ar');
            $config->set('database.default', 'testing');
            $config->set('auth.providers.users.model', User::class);
            $config->set('content.admin.assets', 'vite');
            $config->set('content.layout', 'test-layout');
            $config->set('content.templates', ['default' => ['label' => 'Default', 'view' => 'content::templates.default']]);
            $config->set('content.style_classes.background', ['default' => '', 'muted' => 'bg-muted']);
        });
    }
}
