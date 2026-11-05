<?php

namespace Javaabu\Widgets\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Javaabu\Widgets\Http\Controllers\WidgetsController;

/**
 * Registers the admin routes for `Route::widgets()`
 *
 * ```php
 * Route::widgets();                                                     // /widgets, widgets.*, auth
 * Route::widgets(['guard' => 'web_admin', 'prefix' => 'admin/widgets', 'name' => 'admin.widgets']);
 * ```
 *
 * | Route                                         | Name          | Action  |
 * | --------------------------------------------- | ------------- | ------- |
 * | GET {prefix}                                  | {name}.index  | index   |
 * | GET {prefix}/{widget_location}/create?type=   | {name}.create | create  |
 * | POST {prefix}/{widget_location}               | {name}.store  | store   |
 * | PUT {prefix}/{widget_location}/order          | {name}.order  | order   |
 * | GET {prefix}/{widget_location}/{widget}/edit  | {name}.edit   | edit    |
 * | PATCH {prefix}/{widget_location}/{widget}     | {name}.update | update  |
 * | DELETE {prefix}/{widget_location}/{widget}    | {name}.destroy| destroy |
 */
class WidgetRoutes
{
    /** The route action key that holds the guard the routes were registered for */
    public const string GUARD_ACTION = 'widgets_guard';

    /**
     * @param  array{
     *     guard?: string|null,
     *     prefix?: string,
     *     name?: string,
     *     middleware?: string|array<int, string>|null,
     *     controller?: class-string|null
     * }  $options
     */
    public static function register(Router $router, array $options = []): void
    {
        $guard = $options['guard'] ?? null;
        $prefix = trim($options['prefix'] ?? 'widgets', '/');
        $name = rtrim($options['name'] ?? 'widgets', '.');
        $middleware = $options['middleware'] ?? [$guard ? "auth:{$guard}" : 'auth'];
        $controller = $options['controller'] ?? config('widgets.controllers.widgets', WidgetsController::class);

        $router->group([
            'prefix' => $prefix,
            'as' => "{$name}.",
            'middleware' => $middleware,
            self::GUARD_ACTION => $guard,
        ], function (Router $router) use ($controller) {
            $location = '[A-Za-z0-9_.-]+';

            $router->get('/', [$controller, 'index'])->name('index');
            $router->get('/{widget_location}/create', [$controller, 'create'])->name('create')->where('widget_location', $location);
            $router->post('/{widget_location}', [$controller, 'store'])->name('store')->where('widget_location', $location);
            $router->put('/{widget_location}/order', [$controller, 'order'])->name('order')->where('widget_location', $location);
            $router->get('/{widget_location}/{widget}/edit', [$controller, 'edit'])->name('edit')->where(['widget_location' => $location, 'widget' => '[0-9]+']);
            $router->patch('/{widget_location}/{widget}', [$controller, 'update'])->name('update')->where(['widget_location' => $location, 'widget' => '[0-9]+']);
            $router->delete('/{widget_location}/{widget}', [$controller, 'destroy'])->name('destroy')->where(['widget_location' => $location, 'widget' => '[0-9]+']);
        });
    }

    /**
     * The guard the current route was registered for, or null for the default guard
     */
    public static function guard(Request $request): ?string
    {
        return $request->route()?->getAction(self::GUARD_ACTION);
    }

    /**
     * The route name prefix of the current route, e.g. `admin.widgets`
     */
    public static function namePrefix(Request $request): string
    {
        $name = (string) $request->route()?->getName();

        return str_contains($name, '.')
            ? substr($name, 0, strrpos($name, '.'))
            : 'widgets';
    }
}
