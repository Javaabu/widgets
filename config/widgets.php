<?php

use Javaabu\Widgets\Http\Controllers\WidgetsController;
use Javaabu\Widgets\Models\WidgetInstance;
use Javaabu\Widgets\Policies\WidgetPolicy;
use Javaabu\Widgets\Widgets\ImageWidget;
use Javaabu\Widgets\Widgets\LinkWidget;
use Javaabu\Widgets\Widgets\TextWidget;

return [

    /*
    |--------------------------------------------------------------------------
    | Widget types
    |--------------------------------------------------------------------------
    |
    | The widget classes admins can add. You can also register them from a
    | service provider with Widgets::register(). Remove a built-in one here
    | to hide it.
    |
    */

    'widgets' => [
        TextWidget::class,
        ImageWidget::class,
        LinkWidget::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Locations
    |--------------------------------------------------------------------------
    |
    | Where widgets go, key => label. Labels are translated when shown. For
    | more options (allowed widgets, a maximum, a permission) define them
    | with Widgets::location() in a service provider instead.
    |
    */

    'locations' => [
        // 'sidebar' => 'Sidebar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | The model and table of widget instances. Extend the model to add to it.
    |
    */

    'model' => WidgetInstance::class,

    'table' => 'widget_instances',

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Widgets are loaded once per location per request. Turn the cache on to
    | keep them between requests; saving or deleting a widget clears it.
    | store null means the app's default cache store.
    |
    */

    'cache' => [
        'enabled' => env('WIDGETS_CACHE', false),
        'store' => env('WIDGETS_CACHE_STORE'),
        'prefix' => 'javaabu_widgets',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rendering
    |--------------------------------------------------------------------------
    |
    | The Blade view every widget is wrapped in. It gets $widget, $definition,
    | $location and $slot. A location can set its own with ->wrapper().
    |
    */

    'wrapper' => 'widgets::wrapper',

    /*
    |--------------------------------------------------------------------------
    | Admin pages
    |--------------------------------------------------------------------------
    |
    | The pages Route::widgets() renders: blade (javaabu/forms components and
    | stencil's Blade driver) or inertia (React pages shipped through
    | javaabu/manifest). Requests that expect JSON always get JSON.
    |
    */

    'views' => env('WIDGETS_VIEWS', 'blade'),

    /*
    | The Blade layout the pages extend, and the section they fill.
    */

    'layout' => 'layouts.app',

    'layout_section' => 'content',

    /*
    |--------------------------------------------------------------------------
    | Controllers and authorization
    |--------------------------------------------------------------------------
    |
    | Replace the controller with a subclass, or pass `controller` to
    | Route::widgets(). The policy here is the package default; apps can
    | also override it with config('permissions.policies') or Gate::policy().
    | Turn off register_permissions to register your own.
    |
    */

    'controllers' => [
        'widgets' => WidgetsController::class,
    ],

    'policy' => WidgetPolicy::class,

    'register_permissions' => true,

];
