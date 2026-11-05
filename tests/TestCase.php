<?php

namespace Javaabu\Widgets\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Forms\FormsServiceProvider;
use Javaabu\Manifest\ManifestServiceProvider;
use Javaabu\Permissions\Models\Permission;
use Javaabu\Permissions\Models\Role;
use Javaabu\Permissions\PermissionsServiceProvider;
use Javaabu\Stencil\StencilServiceProvider;
use Javaabu\Translatable\TranslatableServiceProvider;
use Javaabu\Widgets\Facades\Widgets;
use Javaabu\Widgets\Support\Translations;
use Javaabu\Widgets\Tests\TestSupport\Models\Admin;
use Javaabu\Widgets\Tests\TestSupport\Models\User;
use Javaabu\Widgets\Tests\TestSupport\Providers\TestServiceProvider;
use Javaabu\Widgets\WidgetsManager;
use Javaabu\Widgets\WidgetsServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:yWa/ByhLC/GUvfToOuaPD7zDwB64qkc/QkaQOrT5IpE=');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('permission.models.permission', Permission::class);
        $app['config']->set('permission.models.role', Role::class);
        $app['config']->set('permissions.guards', ['web', 'web_admin']);

        $app['config']->set('auth.guards', [
            'web' => ['driver' => 'session', 'provider' => 'users'],
            'web_admin' => ['driver' => 'session', 'provider' => 'admins'],
        ]);

        $app['config']->set('auth.providers', [
            'users' => ['driver' => 'eloquent', 'model' => User::class],
            'admins' => ['driver' => 'eloquent', 'model' => Admin::class],
        ]);
    }

    protected function getPackageProviders($app): array
    {
        $providers = [
            PermissionServiceProvider::class,
            PermissionsServiceProvider::class,
            ManifestServiceProvider::class,
            FormsServiceProvider::class,
            StencilServiceProvider::class,
            WidgetsServiceProvider::class,
            TestServiceProvider::class,
        ];

        if (class_exists(TranslatableServiceProvider::class)) {
            $providers[] = TranslatableServiceProvider::class;
        }

        return $providers;
    }

    protected function tearDown(): void
    {
        Widgets::clearResolvedInstances();
        Translations::$enabled = true;

        parent::tearDown();
    }

    protected function widgets(): WidgetsManager
    {
        return $this->app->make(WidgetsManager::class);
    }
}
