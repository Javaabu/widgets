---
title: Configuration
sidebar_position: 1
---

Everything has a default, and installing the package needs no configuration. Publish the file to change it:

```bash
php artisan vendor:publish --tag=widgets-config
```

| Key | Default | |
| --- | --- | --- |
| `widgets` | Text, Image and Link | The widget classes admins can add. `Widgets::register()` adds more. |
| `locations` | `[]` | `key => label` locations. Use `Widgets::location()` for more options. |
| `model` | `WidgetInstance::class` | The widget model. Extend it to add to it. |
| `table` | `widget_instances` | Its table |
| `cache.enabled` | `false` (`WIDGETS_CACHE`) | Keep each location's widgets in the cache between requests |
| `cache.store` | `null` (`WIDGETS_CACHE_STORE`) | The cache store; `null` is the default store |
| `cache.prefix` | `javaabu_widgets` | The cache key prefix |
| `wrapper` | `widgets::wrapper` | The Blade view each widget is wrapped in |
| `views` | `blade` (`WIDGETS_VIEWS`) | The admin pages' stack: `blade` or `inertia` |
| `layout` | `layouts.app` | The layout the Blade pages extend |
| `layout_section` | `content` | The section they fill |
| `controllers.widgets` | `WidgetsController::class` | The admin controller |
| `policy` | `WidgetPolicy::class` | The default policy for locations and widgets |
| `register_permissions` | `true` | Register the permissions and the policy with javaabu/permissions |

# The facade

`Javaabu\Widgets\Facades\Widgets` (the `WidgetsManager` singleton):

| Method | |
| --- | --- |
| `register(string\|array $widgets)` | Register widget classes |
| `unregister(string $widget)` | Remove one, by class or key |
| `all()`, `find($key)`, `findOrFail($key)`, `exists($key)` | Widget types |
| `available($location)` | The types allowed in a location |
| `location($key, $label = null)` | Define or get a location |
| `locations()`, `findLocation($key)`, `hasLocation($key)`, `removeLocation($key)` | Locations |
| `model()` | The widget model class |
| `widgets($location)`, `visible($location)`, `has($location)` | A location's widgets |
| `render($location)`, `renderWidget($widget, $location = null)` | HTML |
| `toArray($location)`, `props(?array $locations = null)`, `widgetToArray($widget)` | Data for React |
| `flush()` | Forget loaded and cached widgets. Saving or deleting a widget calls it. |
| `registerLocationPermissions()` | Register location permissions. It runs after boot, and again when a location gets one. |
| `Widgets::ignoreMigrations()` | Stop loading the package's migration |
