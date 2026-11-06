---
title: Installation & Setup
sidebar_position: 1.2
---

# Installing

Until the next-gen packages are on Packagist, add their repositories to your app's `composer.json`:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/Javaabu/widgets" },
    { "type": "vcs", "url": "https://github.com/Javaabu/stencil" },
    { "type": "vcs", "url": "https://github.com/Javaabu/forms" },
    { "type": "vcs", "url": "https://github.com/Javaabu/manifest" },
    { "type": "vcs", "url": "https://github.com/Javaabu/permissions" }
]
```

```bash
composer require javaabu/widgets:^1.0@dev
php artisan migrate                # creates the widget_instances table
php artisan permissions:sync       # creates view_widgets and edit_widgets for your guards
```

The service provider is discovered automatically. The migration runs from the package. To publish it and stop the package from loading it, call `Widgets::ignoreMigrations()` in a service provider's `register()`:

```bash
php artisan vendor:publish --tag=widgets-migrations
```

# Defining locations

Locations go in a service provider. Use closures for labels so they're translated when shown:

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Javaabu\Widgets\Facades\Widgets;
use Javaabu\Widgets\Widgets\LinkWidget;
use Javaabu\Widgets\Widgets\TextWidget;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Widgets::location('sidebar', fn () => __('Sidebar'));
        Widgets::location('footer', fn () => __('Footer'))->allow([TextWidget::class, LinkWidget::class])->max(3);
    }
}
```

See [Locations](basic-usage/locations.md) for everything a location can do.

# The admin pages

Register the routes where your admin routes are, with your guard and middleware:

```php
// routes/admin.php
Route::widgets([
    'guard' => 'web_admin',
    'prefix' => 'admin/widgets',
    'name' => 'admin.widgets',
    'middleware' => ['web', 'auth:web_admin'],
]);
```

Give your admin role `edit_widgets`. The Blade pages extend `layouts.app` and fill its `content` section; change that with `widgets.layout` and `widgets.layout_section`. See [Admin pages](basic-usage/admin-pages.md).

# Front-end assets

[javaabu/manifest](https://github.com/Javaabu/manifest) delivers the package's assets:

- `widgets::admin`: the Blade admin page's drag and drop script. The page requires it, and manifest injects it.
- `widgets::styles`: styles for the built-in widgets and the location container (`resources/css/widgets.css`). In Blade, require it where you render widgets, with `@manifestRequire('widgets::styles')`, or write your own CSS. In React, `<WidgetLocation>` and the built-in widget components import it themselves, so Vite bundles it with no import in your app.
- `@javaabu/widgets`: the React components and the Inertia pages. They're built by your app's Vite through manifest's plugin.

Apps that don't build with Vite get the prebuilt script and styles from the package's `dist/`.

# Inertia React

Set the stack and resolve package pages with manifest:

```dotenv
WIDGETS_VIEWS=inertia
```

```tsx
// resources/js/app.tsx
import { createInertiaApp } from "@inertiajs/react"
import { resolvePage } from "@javaabu/manifest/inertia"

createInertiaApp({
  resolve: (name) => resolvePage(name, import.meta.glob("./pages/**/*.tsx")),
  // ...
})
```

Wrap your layout in `FormsProvider` with the Inertia router, as described in @javaabu/forms' Inertia docs, so the settings forms and remove buttons submit as Inertia visits.

# Publishing

| Tag | What |
| --- | --- |
| `widgets-config` | `config/widgets.php` |
| `widgets-migrations` | the migration |
| `widgets-views` | the Blade views, to `resources/views/vendor/widgets` |
| `widgets-translations` | `lang/en.json` and `lang/dv.json`, to `lang/vendor/widgets` |

To override a React page or component, use `php artisan manifest:override widgets js/Pages/Widgets/Index.tsx`.
