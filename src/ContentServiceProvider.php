<?php

namespace PiShift\Content;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use PiShift\Content\Admin\Navigation;
use PiShift\Content\Console\MakeSectionCommand;
use PiShift\Content\Globals\GlobalRegistry;
use PiShift\Content\Sections\SectionRegistry;

class ContentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/content.php', 'content');

        $this->app->singleton(SectionRegistry::class, fn (): SectionRegistry => (new SectionRegistry)->discover(
            config('content.discover.sections.path'),
            config('content.discover.sections.namespace'),
        ));

        $this->app->singleton(GlobalRegistry::class, fn (): GlobalRegistry => (new GlobalRegistry)->discover(
            config('content.discover.globals.path'),
            config('content.discover.globals.namespace'),
        ));

        $this->app->singleton(ContentManager::class);

        $this->app->singleton(Navigation::class, fn (): Navigation => (new Navigation)->add(
            key: 'pages',
            label: 'content::admin.pages',
            url: fn (): string => route('content.admin.pages.index'),
            icon: 'pages',
            ability: 'content.manage',
            active: 'content.admin.pages.*',
            sort: 10,
        )->add(
            key: 'globals',
            label: 'content::admin.globals',
            url: fn (): string => route('content.admin.globals.index'),
            icon: 'settings',
            ability: 'content.manage',
            active: 'content.admin.globals.*',
            sort: 20,
        )->add(
            key: 'media',
            label: 'content::admin.media',
            url: fn (): string => route('content.admin.media.index'),
            icon: 'image',
            ability: 'content.manage',
            active: 'content.admin.media.*',
            sort: 30,
        ));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'content');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'content');
        $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Blade::componentNamespace('PiShift\\Content\\View\\Components', 'content');

        // Let Inertia (and its test assertions) find the admin's page components.
        config(['inertia.pages.paths' => array_values(array_unique([
            ...config('inertia.pages.paths', []),
            __DIR__.'/../resources/admin/pages',
        ]))]);

        // Projects override these in their AuthServiceProvider/AppServiceProvider.
        foreach (['content.manage', 'content.publish'] as $ability) {
            if (! Gate::has($ability)) {
                Gate::define($ability, fn ($user): bool => $user !== null);
            }
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/content.php' => config_path('content.php')], 'content-config');
            $this->publishes([__DIR__.'/../stubs' => base_path('stubs/content')], 'content-stubs');

            $this->commands([MakeSectionCommand::class]);
        }
    }
}
