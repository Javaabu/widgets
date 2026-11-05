<?php

namespace Javaabu\Widgets\Tests\Http;

use Javaabu\Manifest\Facades\Manifest;
use Javaabu\Widgets\Facades\Widgets;
use Javaabu\Widgets\Models\WidgetInstance;
use Javaabu\Widgets\Tests\TestSupport\Models\Admin;
use Javaabu\Widgets\Tests\TestSupport\Models\User;
use Javaabu\Widgets\Tests\TestSupport\Widgets\CounterWidget;
use Javaabu\Widgets\Widgets\LinkWidget;
use Javaabu\Widgets\Widgets\TextWidget;

trait InteractsWithWidgets
{
    protected function defineRoutes($router): void
    {
        $router->middleware('web')->group(function ($router) {
            $router->widgets();
            $router->widgets(['guard' => 'web_admin', 'prefix' => 'admin/widgets', 'name' => 'admin.widgets']);
        });
    }

    protected function setUpWidgets(): void
    {
        Manifest::fake();

        Widgets::register(CounterWidget::class);

        Widgets::location('sidebar', fn () => __('Sidebar'))->description('Next to posts');
        Widgets::location('footer', 'Footer')
            ->allow([TextWidget::class, LinkWidget::class])
            ->max(2)
            ->permission('edit_footer_widgets');

        $this->artisan('permissions:sync');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function user(array $permissions = ['edit_widgets']): User
    {
        $user = User::create([
            'name' => 'އަޙްމަދު',
            'email' => uniqid() . '@example.com',
            'password' => 'secret',
        ]);

        $user->givePermissionTo($permissions);

        return $user;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function admin(array $permissions = ['edit_widgets']): Admin
    {
        $admin = Admin::create([
            'name' => 'Aisha',
            'email' => uniqid() . '@example.com',
            'password' => 'secret',
        ]);

        $admin->givePermissionTo($permissions);

        return $admin;
    }

    protected function add(string $type, array $settings, string $location = 'sidebar', int $order = 0): WidgetInstance
    {
        return WidgetInstance::create([
            'location' => $location,
            'type' => $type,
            'settings' => $settings,
            'order' => $order,
        ]);
    }
}
