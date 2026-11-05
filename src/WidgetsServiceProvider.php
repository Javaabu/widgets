<?php

namespace Javaabu\Widgets;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Javaabu\Manifest\Concerns\RegistersAssets;
use Javaabu\Manifest\Package;
use Javaabu\Permissions\Permissions;
use Javaabu\Widgets\Assets\Assets;
use Javaabu\Widgets\Facades\Widgets;
use Javaabu\Widgets\Policies\WidgetPolicy;
use Javaabu\Widgets\Routing\WidgetRoutes;
use Javaabu\Widgets\Views\Components\Location;

class WidgetsServiceProvider extends ServiceProvider
{
    use RegistersAssets;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/widgets.php', 'widgets');

        $this->app->singleton(WidgetsManager::class);
        $this->app->alias(WidgetsManager::class, 'widgets');
    }

    public function boot(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__ . '/../lang');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'widgets');

        Blade::component('widgets::location', Location::class);

        $this->registerFrontendAssets();
        $this->registerConfiguredWidgets();
        $this->registerRouteMacros();
        $this->registerPermissions();

        if ($this->app->runningInConsole()) {
            if (Widgets::$runs_migrations) {
                $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
            }

            $this->registerPublishing();
        }
    }

    /**
     * The Inertia pages (widgets::Widgets/Index, widgets::Widgets/Edit), the
     * components apps import as @javaabu/widgets, the widget styles and the
     * Blade admin script
     */
    protected function registerFrontendAssets(): void
    {
        $this->registerAssets(Assets::PACKAGE, __DIR__ . '/../resources', function (Package $package) {
            Assets::register($package);
        });
    }

    /**
     * The widget types and locations in config/widgets.php
     */
    protected function registerConfiguredWidgets(): void
    {
        $manager = $this->app->make(WidgetsManager::class);

        $manager->register((array) config('widgets.widgets', []));

        foreach ((array) config('widgets.locations', []) as $key => $label) {
            if (is_int($key)) {
                $manager->location($label);

                continue;
            }

            $manager->location($key, $label);
        }
    }

    protected function registerRouteMacros(): void
    {
        Route::macro('widgets', function (array $options = []) {
            WidgetRoutes::register($this, $options);
        });
    }

    /**
     * view_widgets and edit_widgets, the permissions of locations that have
     * their own, and the default policy, which the app can override
     */
    protected function registerPermissions(): void
    {
        if (! config('widgets.register_permissions', true)) {
            return;
        }

        Permissions::register('widgets', [
            WidgetPolicy::VIEW => 'View widgets',
            WidgetPolicy::EDIT => 'Edit widgets',
        ], label: 'Widgets');

        $policy = config('widgets.policy', WidgetPolicy::class);

        Permissions::policy(WidgetLocation::class, $policy);
        Permissions::policy(Widgets::model(), $policy);

        $this->app->booted(fn () => $this->app->make(WidgetsManager::class)->registerLocationPermissions());
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../config/widgets.php' => config_path('widgets.php'),
        ], 'widgets-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'widgets-migrations');

        $this->publishes([
            __DIR__ . '/../lang' => lang_path('vendor/widgets'),
        ], 'widgets-translations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/widgets'),
        ], 'widgets-views');
    }
}
