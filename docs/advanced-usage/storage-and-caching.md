---
title: Storage and caching
sidebar_position: 4
---

# The model

Each widget an admin adds is a `Javaabu\Widgets\Models\WidgetInstance` row in `widget_instances`:

| Column | |
| --- | --- |
| `id` | |
| `location` | The location key |
| `type` | The widget key |
| `settings` | JSON: the settings, translatable ones as `locale => value` |
| `order` | Position in the location, lowest first |
| `created_at`, `updated_at` | |

Scopes: `inLocation('sidebar')` and `ordered()`. Methods: `definition()` (the `Widget`, or null), `getLocation()`, `setting()`, `localizedSettings()`, `translations()` and `displayTitle()`.

To add to the model, extend it and set `widgets.model`:

```php
namespace App\Models;

use Javaabu\Widgets\Models\WidgetInstance as BaseWidgetInstance;

class WidgetInstance extends BaseWidgetInstance
{
    // relations, casts, activity logging…
}
```

# Adding widgets in code

Seed a location's starting widgets, for example in a seeder:

```php
use Javaabu\Widgets\Models\WidgetInstance;

WidgetInstance::create([
    'location' => 'footer',
    'type' => 'text',
    'settings' => ['title' => ['en' => 'About us', 'dv' => 'އަޅުގަނޑުމެންގެ ވާހަކަ'], 'text' => ['en' => '…']],
    'order' => 0,
]);
```

To validate settings like the admin form does, use the form:

```php
use Javaabu\Widgets\Forms\WidgetForm;

$widget = WidgetForm::for('link', 'footer')->saveWidget([
    'label' => 'ގުޅުއްވާ',
    'url' => '/contact',
]);
```

# Caching

Each location's widgets are loaded at most once per request. To keep them between requests, turn on the cache:

```dotenv
WIDGETS_CACHE=true
WIDGETS_CACHE_STORE=redis
```

The cache keys include a version. Saving or deleting a widget through the model, the admin pages or the order route calls `Widgets::flush()`, which changes the version. If you change rows without the model, such as with a query builder update, call `Widgets::flush()` yourself.

`data()` isn't cached: it runs on every render, so it always shows fresh content. Cache inside `data()` if it's expensive.
